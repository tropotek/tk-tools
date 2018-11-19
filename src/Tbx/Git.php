<?php
namespace Tbx;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Use this to do operations on a Git repository
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
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
     * @var string
     */
    protected $uri = '';

    /**
     * The root directory of the project
     * @var string
     */
    protected $path = '';

    /**
     * If true nothing is committed to the repository
     * @var boolean
     */
    protected $dryRun = false;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    public $output = null;

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    public $input = null;

    /**
     * @var string
     */
    protected $changelog = '';

    /**
     * @var array
     */
    protected $tagList = null;

    /**
     * This will hold any command output text
     * @var null|array
     */
    public $cmdBuf = array();

    /**
     * @var string
     */
    protected $defaultMessage = '';


    /**
     * @param string $path
     * @param bool $dryRun
     * @throws \Exception
     */
    public function __construct($path, $dryRun = false)
    {
        $this->setPath($path);
        $this->dryRun = $dryRun;
    }

    /**
     * @param string $path
     * @param bool $dryRun
     * @return static
     * @throws \Exception
     */
    public static function create($path, $dryRun = false)
    {
        $obj = new static($path, $dryRun);
        return $obj;
    }

    /**
     * Is the path GIT repository
     *
     * @param $path
     * @return bool
     */
    public static function isGit($path)
    {
        $path = rtrim($path, '/');
        return is_dir($path.'/.git');
    }

    /**
     * Is the path a composer package
     *
     * @param $path
     * @return bool
     */
    public static function isComposer($path)
    {
        $path = rtrim($path, '/');
        return file_exists($path.'/composer.json');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this
     * @todo: make individual methods and implement writeLn() to check if output exists
     */
    public function setInputOutput(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        return $this;
    }

    /**
     * @param bool $b
     * @return $this
     */
    public function setDryRun($b = true)
    {
        $this->dryRun = $b;
        $this->writeComment('Dry Run Enabled.');
        return $this;
    }

    /**
     * @return bool
     */
    public function isDryRun()
    {
        return $this->dryRun;
    }

    /**
     * @param $path
     * @return $this
     * @throws \Exception
     */
    public function setPath($path)
    {
        $path = rtrim($path, '/');
        if (!is_dir($path.'/.git')) {
            throw new \Exception('Error: Not a GIT repository - ' . $path);
        }
        $this->path = $path;
        chdir($this->path);
        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * getChangelog
     *
     * @return string
     */
    public function getChangelog()
    {
        return $this->changelog;
    }

    /**
     * Get the current branch
     */
    public function getCurrentBranch()
    {
        $cmd = sprintf('git branch 2>&1 ');
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        exec($cmd, $this->cmdBuf);
        $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERY_VERBOSE);
        foreach($this->cmdBuf as $line) {
            if (preg_match('/^\* (b[0-9]+\.[0-9]+\.[0-9]+)/', $line, $regs)) {
                return $regs[1];
            }
        }
        return 'master';
    }

    /**
     * Get the path for the most recent tag version
     *
     * @return string
     * @deprecated use getCurrentTag()
     */
    public function getCurrentTagFromList()
    {
        $tags = $this->getTagList();
        if (is_array($tags))
            return end($tags);
        return '';
    }

    /**
     * Return the current tag based on the largest version number
     */
    public function getCurrentTag()
    {
        $cmd = sprintf('git describe --abbrev=0 --tags --always 2>&1 ');
        $lastLine = exec($cmd, $this->cmdBuf);
        return $lastLine;
    }

    /**
     * Get an array of current tagged versions.
     *
     * @return array
     */
    public function getTagList()
    {
        if (!$this->tagList) {
            $this->cmdBuf = array();
            $this->tagList = array();

            $cmd = 'git tag 2>&1 ';
            $this->write($cmd, OutputInterface::VERBOSITY_VERY_VERBOSE);
            exec($cmd, $this->cmdBuf);
            $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERY_VERBOSE);

            foreach($this->cmdBuf as $line) {
                $line = trim($line);
                if (!$line) continue;
                if (preg_match('/^([0-9\.]+)/i', $line, $regs)) {
                    $this->tagList[$line] = $line;
                }
            }
            \Tbx\Util::sortVersionArray($this->tagList);
        }
        return $this->tagList;
    }


    /**
     * Get the repository status
     *
     * @return string
     */
    public function getStatus()
    {
        $cmd = sprintf('git status 2>&1 ');
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        $lastLine = exec($cmd, $this->cmdBuf);
        $buff = '';
        if (!preg_match('/^(nothing to commit)|(nothing added to commit)/', $lastLine)) {
            $buff = trim(implode("\n", $this->cmdBuf));
        }
        //$this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_DEBUG);
        return $buff;
    }

    /**
     * Get the repository package base URI
     *
     * @return string
     */
    public function getUri()
    {
        if (!$this->uri) {
            $this->cmdBuf = array();
            $cmd = 'git remote -v 2>&1 ';
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
     *
     * @param string $tagName
     * @return array
     */
    public function diff($tagName)
    {
        if ($tagName == '0.0.0') {
            return array('Tagged initial project');
        }
        $this->cmdBuf = array();
        $tagName = trim($tagName, '/');
        $cmd = 'git diff --name-status 2>&1 '.escapeshellarg($tagName).' HEAD';
        $this->write($cmd, OutputInterface::VERBOSITY_DEBUG);
        exec($cmd, $this->cmdBuf);
        $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_DEBUG);
        $changed = array();
        foreach($this->cmdBuf as $line) {
            if (!preg_match('/^[a-z]\s+(\S+)/i', $line, $regs)) {
                continue;
            }
            if (in_array(trim($regs[1]), $this->getConfig()->get('diff.exclude.files'))) {
                continue;
            }
            $changed[] = trim($regs[1]);
        }
        return $changed;
    }

    /**
     * Returns true if the tag is different than the head
     * IE: master/head has modifications since last release.
     *
     * This can be used to make decisions based on if the two tags
     * have had any modifications, ie: like releasing a version if
     * changes have been committed or not.
     *
     * @param string $tagName The tag/version name of the tag folder
     * @param array  $excludeFiles All files must have the initial / removed as it is assumed relative to the project.
     * @return integer
     */
    public function isDiff($tagName)
    {
        return count($this->diff($tagName));
    }

    /**
     * Commit the current branch and push to remote repos
     *
     * @param string $message
     * @param bool $force
     * @return static
     * @throws \Exception
     */
    public function commit($message = '', $force = false)
    {
        $this->cmdBuf = array();

        $ret = null;

        if (!$force) {
            if (!$message) {
                $message = self::DEFAULT_MESSAGE;
            }
            // Check for any changes in this repository
            $cmd = sprintf('git status -s --untracked-files=no 2>&1 ');
            $this->write($cmd, OutputInterface::VERBOSITY_VERY_VERBOSE);
            $lastLine = exec($cmd, $this->cmdBuf, $ret);
            $this->write($lastLine, OutputInterface::VERBOSITY_VERBOSE);
            if (!$lastLine) return $this;
        }

        // Try committing any changes if any
        $cmd = sprintf('git commit -am %s 2>&1 ', escapeshellarg($message));
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        if (!$this->isDryRun()) {
            $lastLine = exec($cmd, $this->cmdBuf, $ret);
        }
        $this->write($lastLine, OutputInterface::VERBOSITY_VERBOSE);

        if (!$force) {
            if (count($this->cmdBuf) && $lastLine) {
                if (preg_match('/^(nothing to commit)|(nothing added)|(Everything up-to-date)/', $lastLine)) {
                    $this->writeComment('Nothing to commit', OutputInterface::VERBOSITY_NORMAL);
                    return $this;
                } else {
                    $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_NORMAL);
                }
            }
        }

        $this->cmdBuf = array();
        $cmd = sprintf('git push 2>&1 ');
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        if (!$this->isDryRun()) {
            $lastLine = exec($cmd, $this->cmdBuf, $ret);
            $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERBOSE);
        }

        if ($ret) {     // TODO: check if this is the correct response here
            throw new \Exception('Cannot push branch: ' . $lastLine);
        }
        return $this;
    }

    /**
     * update the repository from the remote
     *
     * @throws \Exception
     * @return static
     */
    public function update()
    {
        $this->cmdBuf = array();

        // Does not seem to speed things up any
//        $cmd = sprintf('git fetch --dry-run');
//        $this->write($cmd, OutputInterface::VERBOSITY_VERY_VERBOSE);
//        $lastLine = exec($cmd, $this->cmdBuf, $ret);
//        if (!$lastLine) return $this;

        $cmd = sprintf('git pull 2>&1 ');
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        $lastLine = exec($cmd, $this->cmdBuf, $ret);

        // TODO: Look for a nicer way to handle this
        //$this->writeComment(implode("\n", $this->cmdBuf));
        if (count($this->cmdBuf) && $lastLine) {
            $out = implode("\n", $this->cmdBuf);
            if (preg_match('/error:/', $out)) {
                $this->writeError($out);
            } else if (preg_match('/Already up-to-date/', $lastLine)) {
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
     *
     * @param string $branch
     * @throws \Exception
     */
    public function checkout($branch = 'master')
    {
        $this->cmdBuf = array();
        $cmd = sprintf('git checkout %s 2>&1 ', escapeshellarg($branch));
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        $lastLine = exec($cmd, $this->cmdBuf, $ret);
        $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERBOSE);
        if ($ret) {
            throw new \Exception('Cannot checkout branch: ' . $lastLine);
        }
    }

    /**
     * Get an array of changes to the tag since the last copy command was executed.
     *
     * @param string $version
     * @return array
     */
    protected function makeChangelog($version)
    {
        $exists = array();
        $logs = array();

        $cmd = sprintf('git log --oneline %s..HEAD 2>&1 ', escapeshellarg($version));
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
                if (strlen($msg) <= 2 || preg_match('/^~?Auto/', $msg)) {   // Remove any system messages
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

    /**
     * Tag a repository, basically copy the release to a tag and update the changelog
     *
     * @param string $version A version string in the format of x.x.x
     * @throws \Exception
     */
    protected function tag($version)
    {
        $composerFile = $this->getPath() . '/composer.json';
        $changelogFile = $this->getPath() . '/changelog.md';
        $vb = $this->output->getVerbosity();

        $composerObj = null;
        $composerJson = null;       // Orig master dev composer json, should not be modified
        if (is_file($composerFile)) {
            $composerJson = file_get_contents($composerFile);
            $composerObj = json_decode($composerJson);

            // Setup the new tagged composer.json version
            $composerObj->version = $version;
            $composerObj->time = date('Y-m-d');
            if (property_exists($composerObj, 'minimum-stability')) {
                $composerObj->{'minimum-stability'} = 'stable';

            }
            $this->writeComment('Updating composer.json', OutputInterface::VERBOSITY_VERBOSE);
            file_put_contents($composerFile, \Tbx\Util::jsonPrettyPrint(json_encode($composerObj)));
        }

        // Update the changelog file with any commit messages since the last tag
        $logArr =  $this->makeChangelog($this->getCurrentTag());
        $log = '';
        if (is_array($logArr)) {
            $this->changelog = sprintf("Ver %s [%s]:\n-------------------------------\n", $version, date('Y-m-d'));
            foreach ($logArr as $line) {
                if (str_word_count($line) <= 1)
                    continue;
                $this->changelog .= '' . wordwrap(ucfirst($line), 100, "\n   ") . "\n";
            }
            $log = file_get_contents($changelogFile);
            if ($log && $this->changelog && !preg_match('/Ver\s+'.preg_quote($version).'\s+\[[0-9]{4}\-[0-9]{2}\[0-9]{2}\]/i', $this->changelog)) {
                $logTag = '#CHANGELOG#';
                $changelog = $logTag . "\n\n" . $this->changelog;
                $log = str_replace($logTag, $changelog, $log);
            }
            $this->write($log, OutputInterface::VERBOSITY_VERY_VERBOSE);
        }
        // Save updated changelog file
        if ($log && $this->changelog) {
            $this->writeComment('Updating changelog.md.', OutputInterface::VERBOSITY_VERBOSE);
            if (!$this->isDryRun()) {
                file_put_contents($changelogFile, $log);
            }
        }

        // First Commit before tag to ensure all auto updated file changes are committed
        $currentBranch = $this->getCurrentBranch();

        // TODO: note this $currentBranch will always be master here, we want the prev version tag???s
        $message= 'Tagging branch ' . $currentBranch . ' for release ' . $version;
        $this->writeComment($message);
        $this->output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        $this->commit($message);
        $this->output->setVerbosity($vb);

        $this->cmdBuf = array();
        // Tag trunk
        $message = 'Tagging new release: ' . $version;
        $this->writeComment($message);

        $cmd = sprintf("git tag -a %s -m %s 2>&1 ", $version, escapeshellarg($message) );
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        if (!$this->isDryRun()) {
            exec($cmd, $this->cmdBuf);
            $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERY_VERBOSE);
        }
        $this->cmdBuf = array();
        $cmd = sprintf("git push --tags 2>&1 ");
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        if (!$this->isDryRun()) {
            exec($cmd, $this->cmdBuf);
            $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        // Update composer.json
        if ($composerJson) {
            $this->writeComment('Reverting composer.json', OutputInterface::VERBOSITY_VERBOSE);
            if (!$this->isDryRun()) {
                file_put_contents($composerFile, $composerJson);
            }
            $this->output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
            $this->commit();
            $this->output->setVerbosity($vb);
        }
    }

    /**
     * @param string $version
     * @param array $options
     * @return string
     * @throws \Exception
     */
    public function tagRelease($options, $version = '')
    {
        $version = $this->lookupNewTag($options, $version);
        $this->tag($version);

        return $version;
    }


    public function lookupNewTag($options, $version = '')
    {
        // Get tag/version information
        $currentBranch = $this->getCurrentBranch();
        $curVer = $this->getCurrentTag();
        if (!$curVer) $curVer = '0.0.0';
        $tagList = $this->getTagList();

        // Check if repo has changed since last tag
        if (empty($options['forceTag']) && count($tagList) && version_compare($curVer, '0.0.0', '>') && !$this->isDiff($curVer)) {
            return $curVer;
        }

        $composerObj = null;
        $aliasVer = '';
        $composerFile = $this->getPath() . '/composer.json';
        if (is_file($composerFile)) {
            $composerObj = json_decode(file_get_contents($composerFile));
            if ($composerObj) {     // Find branch-alias so we can get the major version X.X.x-dev
                if (isset($composerObj->extra->{'branch-alias'}->{'dev-master'})) {
                    $aliasVer = $composerObj->extra->{'branch-alias'}->{'dev-master'};
                    $aliasVer = str_replace(array('.x-dev'), '.' . self::MAX_VER, $aliasVer);
                }
            }
        }
        if (!$version || version_compare($version, $curVer, '<')) {
            $version = \Tbx\Util::incrementVersion($curVer, $aliasVer);
            if (empty($options['notStable']) && ((int)substr($version, strrpos($version, '.')+1) % 2) > 0) {
                $version = \Tbx\Util::incrementVersion($version, $aliasVer);
            }
        }

        if (version_compare($version, end($tagList), '<=')) {
            $this->writeError('Version mismatch, check that you have the latest version of the project checked out.');
            return $curVer;
        }

        return $version;
    }



    /**
     * @return \Tk\Config
     */
    public function getConfig()
    {
        return \Tk\Config::getInstance();
    }


    protected function writeStrong($str = '', $options = OutputInterface::VERBOSITY_NORMAL)
    {
        return $this->write(sprintf('<options=bold>%s</>', $str), $options);
    }
    protected function writeInfo($str, $options = OutputInterface::VERBOSITY_NORMAL)
    {
        return $this->write(sprintf('<info>%s</info>', $str), $options);
    }

    protected function writeComment($str, $options = OutputInterface::VERBOSITY_NORMAL)
    {
        return $this->write(sprintf('<comment>%s</comment>', $str), $options);
    }

    protected function writeQuestion($str, $options = OutputInterface::VERBOSITY_NORMAL)
    {
        return $this->write(sprintf('<question>%s</question>', $str), $options);
    }

    protected function writeError($str, $options = OutputInterface::VERBOSITY_NORMAL)
    {
        return $this->write(sprintf('<error>%s</error>', $str), $options);
    }

    protected function write($str, $options = OutputInterface::VERBOSITY_NORMAL)
    {
        if ($this->output)
            return $this->output->writeln($str, $options);
    }

}