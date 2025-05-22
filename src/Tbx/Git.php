<?php
namespace Tbx;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tk\Config;

/**
 * Use this to do operations on a Git repository
 *
 * @author Tropotek <https://tropotek.com/>
 */
class Git
{

    /**
     * The default commit message
     */
    const DEFAULT_MESSAGE = '~Auto: Commit';

    /**
     * This is used when updating the composer file
     */
    const MAX_VER = 99999999999;

    /**
     * The repository base URI, all paths used should
     * be prepended with this base uri.
     */
    protected string $uri = '';

    /**
     * The root directory of the project
     */
    protected string $path = '';

    protected string $name = '';

    public ?OutputInterface $output = null;

    public ?InputInterface $input = null;

    protected string $changelog = '';

    protected array $tagList = [];

    /**
     * This will hold any command output text
     */
    public array $cmdBuf = [];

    protected string $defaultMessage = '';

    protected array $options = [];

    protected ?\stdClass $composerObj = null;


    public function __construct(string $path, array $options = [])
    {
        $this->setPath($path);
        $this->options = $options;
    }

    public static function create(string $path, array $options = []): self
    {
        $obj = new self($path, $options);
        return $obj;
    }

    public static function isGit(string $path): bool
    {
        $path = rtrim($path, '/');
        return is_dir($path.'/.git');
    }

    public function setDryRun(bool $b = true): self
    {
        $this->options['dryRun'] = $b;
        $this->writeComment('Dry Run Enabled.');
        return $this;
    }

    public function isDryRun(): bool
    {
        return $this->getOption('dryRun', false);
    }

    /**
     * Is the path a composer package
     */
    public static function isComposer(string $path): bool
    {
        $path = rtrim($path, '/');
        return file_exists($path.'/composer.json');
    }

    /**
     * Try to load a composer.json for this repository
     */
    public function getComposer(): ?\stdClass
    {
        if (self::isComposer($this->getPath())) {
            $composerFile = $this->getPath() . '/composer.json';
            if (is_file($composerFile)) {
                $this->composerObj = \Tbx\Util::jsonDecode((string)file_get_contents($composerFile));
            }
        }
        return $this->composerObj;
    }

    public function getName(): string
    {
        if (!$this->name) {
            $this->name = basename($this->getPath());
            if ($this->getComposer()) {
                $composerObj = $this->getComposer();
                if (property_exists($composerObj, 'name')) {
                    $this->name = $composerObj->name ?? '';
                }
            }
        }
        return $this->name;
    }

    public function setPath(string $path): self
    {
        $path = rtrim($path, '/');
        if (!is_dir($path.'/.git')) {
            throw new \Exception('Error: Not a GIT repository - ' . $path);
        }
        $this->path = $path;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getGitArgs(): string
    {
        return ' -C ' . escapeshellarg($this->getPath());
    }

    /**
     * getChangelog
     */
    public function getChangelog(): string
    {
        return $this->changelog;
    }

    /**
     * Get the current branch
     */
    public function getCurrentBranch(): string
    {
        $cmd = sprintf('git %s branch 2>&1 ', $this->getGitArgs());
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        exec($cmd, $this->cmdBuf);
        $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERY_VERBOSE);
        foreach($this->cmdBuf as $line) {
            if (preg_match('/^\* (.+)/', $line, $regs)) {
                return $regs[1];
            }
        }

        return 'master';
    }

    /**
     * Get the repository status
     */
    public function getStatus(): string
    {
        $cmd = sprintf('git %s status 2>&1 ', $this->getGitArgs());
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        $lastLine = (string)exec($cmd, $this->cmdBuf);
        $buff = '';
        if (!preg_match('/^(nothing to commit)|(nothing added to commit)/', $lastLine)) {
            $buff = trim(implode("\n", $this->cmdBuf));
        }
        return $buff;
    }

    /**
     * Get the repository package base URI
     */
    public function getUri(): string
    {
        if (!$this->uri) {
            $this->cmdBuf = [];
            $cmd = sprintf('git %s remote -v 2>&1 ', $this->getGitArgs());
            $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
            exec($cmd, $this->cmdBuf);
            $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERBOSE);

            foreach ($this->cmdBuf as $line) {
                if (preg_match('/^origin\s+(\S+)\s+\((fetch|push)\)/', trim($line), $regs)) {
                    $this->uri = $regs[1];
                    break;
                }
            }
        }
        return $this->uri;
    }

    /**
     * Check to see if the given tag name has changes to the HEAD of the repository
     * returns a list of changed files
     */
    public function diff(string $tagName): array
    {
        if ($tagName == '0.0.0') {
            return ['Tagged initial project'];
        }
        $this->cmdBuf = [];
        $tagName = trim($tagName, '/');
        $cmd = sprintf('git %s diff --name-status 2>&1 %s HEAD', $this->getGitArgs(), escapeshellarg($tagName));
        $this->write($this->getPath(), OutputInterface::VERBOSITY_VERY_VERBOSE);
        $this->write($cmd, OutputInterface::VERBOSITY_VERY_VERBOSE);
        exec($cmd, $this->cmdBuf);
        $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERY_VERBOSE);
        $changed = [];
        foreach($this->cmdBuf as $line) {
            if (!preg_match('/^[a-z]\s+(\S+)/i', $line, $regs)) {
                continue;
            }
            if (in_array(trim($regs[1]), Config::instance()->get('diff.exclude.files'))) {
                continue;
            }
            $changed[] = trim($regs[1]);
        }
        return $changed;
    }

    /**
     * Returns true if the tag is different than `head`
     * IE: master/head has modifications since last release.
     *
     * This can be used to make decisions based on if the two tags
     * have had any modifications, ie: like releasing a version if
     * changes have been committed or not.
     */
    public function isDiff(string $tagName): bool|int
    {
        return
            preg_match('/\.x$/', $tagName) ||        // if no major version exists
            count($this->diff($tagName));
    }

    /**
     * Commit the current branch and push to remote repos
     */
    public function commit(string $message = '', bool $force = false): self
    {
        $this->cmdBuf = [];
        $ret = null;
        if (!$force) {
            if (!$message) {
                $message = self::DEFAULT_MESSAGE;
            }
            // Check for any changes in this repository
            $cmd = sprintf('git %s status -s --untracked-files=no 2>&1 ', $this->getGitArgs());
            $this->write($cmd, OutputInterface::VERBOSITY_VERY_VERBOSE);
            $lastLine = (string)exec($cmd, $this->cmdBuf, $ret);
            $this->write($lastLine, OutputInterface::VERBOSITY_VERBOSE);
            if (!$lastLine) return $this;
        }

        // Try committing any changes if any
        $cmd = sprintf('git %s commit -am %s 2>&1 ', $this->getGitArgs(), escapeshellarg($message));
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        $lastLine = '';
        if (!$this->isDryRun()) {
            passthru($cmd, $ret);
        }

        if (!$force) {
            if (count($this->cmdBuf)) {
                if (preg_match('/^(nothing to commit)|(nothing added)|(Everything up-to-date)/', $lastLine)) {
                    $this->writeComment('Nothing to commit');
                    return $this;
                } else {
                    $this->writeComment(implode("\n", $this->cmdBuf));
                }
            }
        }

        $this->cmdBuf = [];
        $cmd = sprintf('git %s push 2>&1 ', $this->getGitArgs());
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        if (!$this->isDryRun()) {
            exec($cmd, $this->cmdBuf, $ret);
            $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERBOSE);
        }

        if ($ret) {
            $this->writeError("Cannot push updates, external changes exist, manually pull first then push.");
            throw new \Exception('Failed to push updates');
        }
        return $this;
    }

    /**
     * update the repository from the remote
     */
    public function update(): self
    {
        $this->cmdBuf = [];

        $cmd = sprintf('git %s pull 2>&1 ', $this->getGitArgs());
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        $lastLine = exec($cmd, $this->cmdBuf, $ret);

        if (count($this->cmdBuf) && $lastLine) {
            $out = implode("\n", $this->cmdBuf);
            if (str_contains($out, 'error:')) {
                $this->writeError($out);
            } else if (str_contains($lastLine, 'Already up-to-date')) {
                $this->writeComment('Already up-to-date');
            } else if (preg_match('/([0-9]+) files? changed/', $lastLine, $reg)) {
                $this->writeComment('  + ' . $reg[1] . ' files changed');
            } else {
                $this->writeComment($out);
            }
        }
        return $this;
    }

    /**
     * Checkout a branch
     */
    public function checkout(string $branch = 'master'): void
    {
        $this->cmdBuf = [];
        $cmd = sprintf('git %s checkout %s 2>&1 ', $this->getGitArgs(), escapeshellarg($branch));
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        $lastLine = exec($cmd, $this->cmdBuf, $ret);
        $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERBOSE);
        if ($ret) {
            throw new \Exception('Cannot checkout branch: ' . $lastLine);
        }
    }

    /**
     * Get an array of changes to the tag since the last copy command was executed.
     */
    protected function makeChangelog(string $version): array
    {
        $exists = [];
        $logs = [];

        $cmd = sprintf('git %s log --oneline %s..HEAD 2>&1 ', $this->getGitArgs(), escapeshellarg($version));
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        exec($cmd, $this->cmdBuf, $ret);
        $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERBOSE);
        if ($ret) {
            return $logs;
        }

        foreach ($this->cmdBuf as $i => $log) {
            if (!preg_match('/^([0-9a-f]{7,10})\s+(.+)/i', $log, $regs)) {
                continue;
            }
            $msgLine = trim($regs[2]);
            $msgLines = explode('- ', $msgLine);
            foreach($msgLines as $msg) {
                $msg = trim($msg);
                if (strlen($msg) <= 2 || preg_match('/^~?Auto/', $msg) || $msg == 'Revert branch to dev mode') {   // Remove any system messages
                    $this->writeComment('$msg(-) => ' . $msg, OutputInterface::VERBOSITY_VERY_VERBOSE);
                    continue;
                } else {
                    $this->writeComment('$msg(+) => ' . $msg, OutputInterface::VERBOSITY_VERY_VERBOSE);
                }
                if (!in_array(md5($msg), $exists)) {
                    $logs[] = '  - ' . $msg;
                    $exists[] = md5($msg);
                }
            }
        }
        return $logs;
    }

    public function tagRelease(string $tagName = ''): string
    {
        $curTag = $this->getCurrentTag($this->getBranchAlias());
        if (!$tagName || !preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)$/', $tagName)) {
            $tagName = $this->lookupNextTag($curTag);
        }
        if ($this->canCreateTag($curTag)) {
            $this->tag($tagName);
            return $tagName;
        }
        return $curTag;
    }

    /**
     * Tag a repository, basically copy the release to a tag and update the changelog
     */
    protected function tag(string $version): void
    {
        $composerFile = $this->getPath() . '/composer.json';
        $changelogFile = $this->getPath() . '/changelog.md';
        $versionFile = $this->getPath() . '/version.md';
        $vb = $this->output->getVerbosity();
        $composerJson = '';
        $isProject = false;

        // update the release composer file
        if (is_file($composerFile)) {
            $composerJson = (string)file_get_contents($composerFile);
            $composerObj = \Tbx\Util::jsonDecode($composerJson);
            $isProject = ($composerObj->type == 'project');

            if (!$this->isDryRun()) {
                file_put_contents($versionFile, $version);
            }

            $composerObj->time = date('Y-m-d');
            if (property_exists($composerObj, 'minimum-stability')) {
                $composerObj->{'minimum-stability'} = 'stable';
            }
            $this->writeComment('Updating composer.json', OutputInterface::VERBOSITY_VERBOSE);
            if (!$this->isDryRun()) {
                file_put_contents($composerFile, \Tbx\Util::jsonEncode($composerObj));
            }

            if ($isProject) {
                // run composer update to set stable sources in composer.lock file
                $cmd = sprintf("composer update 2>&1 ");
                if (!$this->isDryRun()) {
                    exec($cmd, $this->cmdBuf);
                    $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERY_VERBOSE);
                }
            }
        }

        // Update the release changelog file
        $logArr = $this->makeChangelog($this->getCurrentTag());

        $this->changelog = sprintf("Ver %s [%s]:\n-------------------------------\n", $version, date('Y-m-d'));
        foreach ($logArr as $line) {
            if (str_word_count($line) <= 1)
                continue;
            $this->changelog .= wordwrap(ucfirst($line), 100, "\n   ") . "\n";
        }

        $log = @file_get_contents($changelogFile);
        if ($log === false) {
            $log = '';
        }

        if ($log && $this->changelog && !preg_match('/Ver\s+' . preg_quote($version, '/') . '\s+\[[0-9]{4}\-[0-9]{2}\[0-9]{2}\]/i', $this->changelog)) {
            $logTag = '#CHANGELOG#';
            $changelog = $logTag . "\n\n" . $this->changelog;
            $log = str_replace($logTag, $changelog, $log);
        }
        $this->write($this->changelog, OutputInterface::VERBOSITY_VERY_VERBOSE);

        // Save release changelog file
        if ($log && $this->changelog) {
            $this->writeComment('Updating changelog.md.', OutputInterface::VERBOSITY_VERBOSE);
            if (!$this->isDryRun()) {
                file_put_contents($changelogFile, $log);
            }
        }

        $cmd = sprintf('git %s add . 2>&1 ', $this->getGitArgs());
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        if (!$this->isDryRun()) {
            exec($cmd, $this->cmdBuf);
        }

        $currentBranch = $this->getCurrentBranch();
        $message = 'Tagging and releasing branch `' . $currentBranch . '` with version `' . $version .'`.';
        $this->writeComment($message);

        $this->output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        $this->commit($message);
        $this->output->setVerbosity($vb);

        // Tag the repository
        $this->cmdBuf = [];
        $cmd = sprintf("git %s tag -a %s -m %s 2>&1 ", $this->getGitArgs(), $version, escapeshellarg($message) );
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        if (!$this->isDryRun()) {
            exec($cmd, $this->cmdBuf);
            $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        // Push the tag to the remote repository
        $this->cmdBuf = [];
        $cmd = sprintf("git %s push --tags 2>&1 ", $this->getGitArgs());
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        if (!$this->isDryRun()) {
            exec($cmd, $this->cmdBuf);
            $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        // Restore composer.lock file back to dev libs
        if ($composerJson) {
            $this->writeComment('Restoring branch composer.json', OutputInterface::VERBOSITY_VERBOSE);
            if (!$this->isDryRun()) {
                file_put_contents($composerFile, $composerJson);
            }

            if ($isProject) {
                // run composer update to return to dev sources
                $cmd = sprintf("composer update 2>&1 ");
                if (!$this->isDryRun()) {
                    exec($cmd, $this->cmdBuf);
                    $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERY_VERBOSE);
                }
            }
            $this->output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
            $this->commit('Revert branch to dev mode');

            $this->output->setVerbosity($vb);
        }
    }

    /**
     * Return the branch alias from the composer object as this is what we use to
     * determine the next release version
     * EG:
     *   3.0.x-dev
     */
    public function getBranchAlias(): string
    {
        $alias = '';
        if ($this->getComposer()) {
            if (isset($this->getComposer()->extra->{'branch-alias'}->{'dev-master'})) {
                $alias = $this->getComposer()->extra->{'branch-alias'}->{'dev-master'};
                // TODO we need to handle 3.0.x-dev and 3.0-dev better
                if (preg_match('/\.[0-9]+-dev$/', $alias)) {
                    $alias = str_replace('-dev', '.x-dev', $alias);
                }
            }
        }
        return $alias;
    }

    /**
     * Clean a version alias string:
     *
     *   - 3.0-dev => 3.0.x
     *   - 3.0.x-dev => 3.0.x
     *   - 3.0 => 3.0.x
     */
    protected function cleanAlias(string $verAlias = '1.0.x-dev'): string
    {
        $verAlias = trim($verAlias, ". \t\n\r\0\x0B");
        $verAlias = str_replace('.x-dev', '.x', $verAlias);
        $verAlias = str_replace('-dev', '.x', $verAlias);
        if (!preg_match('/\.x$/', $verAlias)) {
            $verAlias = $verAlias . '.x';
        }
        return $verAlias;
    }

    /**
     * Return the current tag based on the largest version number for this branch
     *
     * @param string $branchAlias If no branch alias is supplied then return the current tag for the project
     */
    public function getCurrentTag(string $branchAlias = ''): string
    {
        $ver = '1.0.x';
        $tags = $this->getTagList();
        if ($branchAlias) {
            $verPrefix = substr($branchAlias, 0, (int)strrpos($branchAlias, '.'));
            $ver = $verPrefix.'.x';
            foreach ($tags as $tag) {
                if (preg_match('/^'.preg_quote($verPrefix, '/').'/', $tag)) {
                    $ver = $tag;
                }
            }
        } else {
            if (is_array($tags) && count($tags)) {
                $v = $tags[count($tags)-1];
                if ($v) $ver = $v;
            }
        }
        return $ver;
    }

    public function getRecentTag(): string
    {
        $cmd = sprintf('git %s describe --abbrev=0 2>&1 ', $this->getGitArgs());
        $this->write($cmd, OutputInterface::VERBOSITY_VERY_VERBOSE);
        exec($cmd, $this->cmdBuf);
        $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_DEBUG);
        return trim($this->cmdBuf[0]);
    }

    /**
     *
     * @param string $curTag Either a static tag version or a branch-alias to increment
     */
    public function getNextTagName(string $curTag = ''): string
    {
        $alias = substr($curTag, 0, (int)strrpos($curTag, '.')).'.x';
        if (preg_match('/\.x-dev$/', $curTag)) {
            $curTag = $this->getCurrentTag($curTag);
            $alias = substr($curTag, 0, (int)strrpos($curTag, '.')).'.x';
        }
        $step = 2;
        $notStable = $this->getOption('notStable', false);
        if ($notStable) {   // increment ver by 1
            $step = 1;
        } else {
            if (((int)substr($curTag, (int)strrpos($curTag, '.')+1) % 2) > 0) {
                $step = 1;
            }
        }
        return \Tbx\Util::incrementVersion($curTag, $alias, $step);
    }

    public function canCreateTag(string $curTag = ''): bool
    {
        if (
            $this->getOption('forceTag', false) ||
            !count($this->getTagList()) ||
            $this->isDiff($curTag))
        {
            return true;
        }
        return false;
    }

    /**
     * This will return the next version number if the repo can be tagged
     * Repositories that have no modifications will return the current version tag.
     */
    public function lookupNextTag(string $curTag = ''): string
    {
        $nextTag = $curTag;
        if ($this->canCreateTag($curTag)) {
            $nextTag = $this->getNextTagName($curTag);
        }
        return $nextTag;
    }

    /**
     * Get an array of current tagged versions.
     */
    public function getTagList(): ?array
    {
        if (!$this->tagList) {
            $this->cmdBuf = [];
            $this->tagList = [];

            $cmd = sprintf('git %s tag 2>&1 ', $this->getGitArgs());
            $this->write($cmd, OutputInterface::VERBOSITY_VERY_VERBOSE);
            exec($cmd, $this->cmdBuf);
            //$this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_DEBUG);


            foreach($this->cmdBuf as $line) {
                $line = trim($line);
                if (!$line) continue;
                if (preg_match('/^([0-9.]+)/i', $line, $regs)) {
                    $this->tagList[$line] = $line;
                }
            }
            \Tbx\Util::sortVersionArray($this->tagList);
        }
        return $this->tagList;
    }

    public function hasOption(string $name): bool
    {
        return !empty($this->options[$name]);
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        if ($this->hasOption($name)) {
            return $this->options[$name];
        }
        return $default;
    }

    public function setInputOutput(InputInterface $input, OutputInterface $output): self
    {
        $this->input = $input;
        $this->output = $output;
        return $this;
    }

    public function getOutput(): ?OutputInterface
    {
        return $this->output;
    }

    public function getInput(): ?InputInterface
    {
        return $this->input;
    }

    protected function writeStrong(string $str = '', int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<options=bold>%s</>', $str), $options);
    }

    protected function writeInfo(string $str, int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<info>%s</info>', $str), $options);
    }

    protected function writeComment(string $str, int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<comment>%s</comment>', $str), $options);
    }

    protected function writeQuestion(string $str, int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<question>%s</question>', $str), $options);
    }

    protected function writeError(string $str, int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<error>%s</error>', $str), $options);
    }

    protected function write(string $str, int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        if ($this->getOutput())
            $this->getOutput()->writeln($str, $options);
    }

}