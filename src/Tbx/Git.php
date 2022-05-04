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
     * @var string
     */
    protected $name = null;


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
     * @var array
     */
    protected $options = array();

    /**
     * @var null
     */
    protected $composerObj = null;

    /**
     * If true nothing is committed to the repository
     * @var boolean
     * @deprcated Use isDryRun()
     */
    protected $dryRun = false;


    /**
     * @param string $path
     * @param array $options
     * @throws \Exception
     */
    public function __construct($path, $options = array())
    {
        $this->setPath($path);
        $this->options = $options;
    }

    /**
     * @param string $path
     * @param array $options
     * @return static
     * @throws \Exception
     */
    public static function create($path, $options = array())
    {
        $obj = new static($path, $options);
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
     * @param bool $b
     * @return $this
     */
    public function setDryRun($b = true)
    {
        $this->options['dryRun'] = $b;
        $this->writeComment('Dry Run Enabled.');
        return $this;
    }

    /**
     * @return bool
     */
    public function isDryRun()
    {
        return $this->getOption('dryRun', false);
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
     * Try to load a composer.jsong for this repository
     *
     * @return null|\stdClass
     */
    public function getComposer()
    {
        if (!$this->composerObj && self::isComposer($this->getPath())) {
            $composerFile = $this->getPath() . '/composer.json';
            if (is_file($composerFile)) {
                $this->composerObj = \Tbx\Util::jsonDecode(file_get_contents($composerFile));
            }
        }
        return $this->composerObj;
    }

    /**
     * @return string
     */
    public function getName()
    {
        if (!$this->name) {
            $this->name = basename($this->getPath());
            if ($this->getComposer()) {
                $composerObj = $this->getComposer();
                if ($composerObj && property_exists($composerObj, 'name')) {
                    $this->name = $composerObj->name;
                }
            }
        }
        return $this->name;
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
     * @return string
     */
    public function getGitArgs()
    {
        return ' -C ' . escapeshellarg($this->getPath());
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
        // TODO: This is not working as expected???
        //       [3.0.58]    tk-table
        //       Tagging and releasing branch `M Tk/Table/Cell/Iface.php` with version `3.0.60`.
        // Array[6]
        //(
        //  [0] => M      composer.json
        //  [1] => M      src/App/Controller/Animal/View.php
        //  [2] => M      src/App/Form/Animal.php
        //  [3] => 92276c8 ~Auto: Commit
        //  [4] => 256f534 ~Auto: Commit
        //  [5] => 3.0                          <<--- This is what we need
        //
        //)
//        $cmd = sprintf('git %s rev-parse --abbrev-ref HEAD 2>&1 ', $this->getGitArgs());
//        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
//        exec($cmd, $this->cmdBuf);
//        $this->write(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERY_VERBOSE);
//        vd($this->cmdBuf);
//        if (count($this->cmdBuf)) return $this->cmdBuf[0];

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
     *
     * @return string
     */
    public function getStatus()
    {
        $cmd = sprintf('git %s status 2>&1 ', $this->getGitArgs());
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
        $cmd = sprintf('git %s diff --name-status 2>&1 %s HEAD', $this->getGitArgs(), escapeshellarg($tagName));
        $this->write($this->getPath(), OutputInterface::VERBOSITY_VERY_VERBOSE);
        $this->write($cmd, OutputInterface::VERBOSITY_VERY_VERBOSE);
        exec($cmd, $this->cmdBuf);
        $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERY_VERBOSE);
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
     * @return integer
     */
    public function isDiff($tagName)
    {
        return
            preg_match('/\.x$/', $tagName) ||        // if no major version exists
            count($this->diff($tagName));
    }

    /**
     * Commit the current branch and push to remote repos
     *
     * @todo We need to call git pull if there is a sync error with the remote,
     *       then re-push the code again...
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
            $cmd = sprintf('git %s status -s --untracked-files=no 2>&1 ', $this->getGitArgs());
            $this->write($cmd, OutputInterface::VERBOSITY_VERY_VERBOSE);
            $lastLine = exec($cmd, $this->cmdBuf, $ret);
            $this->write($lastLine, OutputInterface::VERBOSITY_VERBOSE);
            if (!$lastLine) return $this;
        }

        // Try committing any changes if any
        $cmd = sprintf('git %s commit -am %s 2>&1 ', $this->getGitArgs(), escapeshellarg($message));
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        if (!$this->isDryRun()) {
            $lastLine = exec($cmd, $this->cmdBuf, $ret);
            vd($lastLine);
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
        $cmd = sprintf('git %s push 2>&1 ', $this->getGitArgs());
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        if (!$this->isDryRun()) {
            $lastLine = exec($cmd, $this->cmdBuf, $ret);
            vd($lastLine);
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

        $cmd = sprintf('git %s pull 2>&1 ', $this->getGitArgs());
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        $lastLine = exec($cmd, $this->cmdBuf, $ret);

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
     *
     * @param string $version
     * @return array
     */
    protected function makeChangelog($version)
    {
        $exists = array();
        $logs = array();

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
     *
     *
     * @param string $tagName If not set then it will be automatically generated from the composerJson branch alias
     * @return string
     * @throws \Exception
     */
    public function tagRelease($tagName = '')
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
     *
     * @param string $version A version string in the format of x.x.x
     * @throws \Exception
     */
    protected function tag($version)
    {
        $composerFile = $this->getPath() . '/composer.json';
        $changelogFile = $this->getPath() . '/changelog.md';
        $versionFile = $this->getPath() . '/version.md';
        $vb = $this->output->getVerbosity();

        $composerObj = null;
        $composerJson = null;

        // update the release composer file
        if (is_file($composerFile)) {
            $composerJson = file_get_contents($composerFile);
            $composerObj = \Tbx\Util::jsonDecode($composerJson);
            if (!$composerObj) $composerObj = new \stdClass();

            // TODO: I think we need a version.md file instead, as composer is validating its .json file now
            //$composerObj->version = $version;
            if (!$this->isDryRun()) {
                file_put_contents($versionFile, $version);
            }

            $composerObj->time = date('Y-m-d');
            if (property_exists($composerObj, 'minimum-stability')) {
                $composerObj->{'minimum-stability'} = 'stable';
            }
            $this->writeComment('Updating composer.json', OutputInterface::VERBOSITY_VERBOSE);
            //vd(\Tbx\Util::jsonEncode($composerObj));
            if (!$this->isDryRun()) {
                file_put_contents($composerFile, \Tbx\Util::jsonEncode($composerObj));
            }
        }

        // Update the release changelog file
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
            $this->write($this->changelog, OutputInterface::VERBOSITY_VERY_VERBOSE);
        }
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
        $this->cmdBuf = array();
        $cmd = sprintf("git %s tag -a %s -m %s 2>&1 ", $this->getGitArgs(), $version, escapeshellarg($message) );
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        if (!$this->isDryRun()) {
            exec($cmd, $this->cmdBuf);
            $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        // Push the tag to the remote repository
        $this->cmdBuf = array();
        $cmd = sprintf("git %s push --tags 2>&1 ", $this->getGitArgs());
        $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
        if (!$this->isDryRun()) {
            exec($cmd, $this->cmdBuf);
            $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_VERY_VERBOSE);
        }


        // Restore the dev composer.json
        if ($composerJson) {
            $this->writeComment('Restoring branch composer.json', OutputInterface::VERBOSITY_VERBOSE);
            if (!$this->isDryRun()) {
                file_put_contents($composerFile, $composerJson);
            }
            $this->output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
            $this->commit();
            $this->output->setVerbosity($vb);
        }
    }

    /**
     * Return the branch alias from the composer object as this is what we use to
     * determin the next release version
     * EG:
     *   3.0.x-dev
     *
     * @return string
     */
    public function getBranchAlias()
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
     *
     * @param string $verAlias
     * @return string|string[]
     */
    private function cleanAlias($verAlias = '1.0.x-dev')
    {
        $verAlias = trim($verAlias, ". \t\n\r\0\x0B");
        $verAlias = str_replace('.x-dev', '.x', $verAlias);
        $verAlias = str_replace('-dev', '.x', $verAlias);
        if (!preg_match('/\.x$/', $verAlias))
            $verAlias = $verAlias.'.x';
        return $verAlias;
    }

    /**
     * Return the current tag based on the largest version number for this branch
     *
     *
     * @param null|string $branchAlias If no branch alias is supplied then return the current tag for the project
     * @return string
     */
    public function getCurrentTag($branchAlias = '')
    {
        $ver = '1.0.x';
        $tags = $this->getTagList();
        if ($branchAlias) {
            $verPrefix = substr($branchAlias, 0, strrpos($branchAlias, '.'));
            $ver = $verPrefix.'.x';
            foreach ($tags as $tag) {
                if (preg_match('/^'.preg_quote($verPrefix).'/', $tag)) {
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

    /**
     *
     * @param string $curTag Either a static tag version or a branch-alias to increment
     * @return string
     */
    public function getNextTagName($curTag = '')
    {
        $alias = substr($curTag, 0, strrpos($curTag, '.')).'.x';
        if (preg_match('/\.x-dev$/', $curTag)) {
            $curTag = $this->getCurrentTag($curTag);
            $alias = substr($curTag, 0, strrpos($curTag, '.')).'.x';
        }
        $step = 2;
        $notStable = $this->getOption('notStable', false);
        if ($notStable) {   // increment ver by 1
            $step = 1;
        } else {
            if (((int)substr($curTag, strrpos($curTag, '.')+1) % 2) > 0) {
                $step = 1;
            }
        }
        $nextTag = \Tbx\Util::incrementVersion($curTag, $alias, $step);
        return $nextTag;
    }

    /**
     * @param string $curTag
     * @return bool
     */
    public function canCreateTag($curTag = '')
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
     *
     * @param string $curTag
     * @return string
     */
    public function lookupNextTag($curTag = '')
    {
        $nextTag = $curTag;
        if ($this->canCreateTag($curTag)) {
            $nextTag = $this->getNextTagName($curTag);
        }
        return $nextTag;
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

            $cmd = sprintf('git %s tag 2>&1 ', $this->getGitArgs());
            $this->write($cmd, OutputInterface::VERBOSITY_VERY_VERBOSE);
            exec($cmd, $this->cmdBuf);
            $this->writeComment(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_DEBUG);

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
     * @return \Tk\Config
     */
    public function getConfig()
    {
        return \Tk\Config::getInstance();
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasOption($name)
    {
        return !empty($this->options[$name]);
    }

    /**
     * @param string $name
     * @param null|mixed $default
     * @return mixed|null
     */
    public function getOption($name, $default = null)
    {
        if ($this->hasOption($name)) {
            return $this->options[$name];
        }
        return $default;
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
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return InputInterface
     */
    public function getInput()
    {
        return $this->input;
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
        if ($this->getOutput())
            return $this->getOutput()->writeln($str, $options);
    }

}