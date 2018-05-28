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
     * The libs in the project folder to iterate through
     * @var array
     */
    public static $VENDOR_PATHS = array(
        '/vendor/fvas', '/vendor/ttek', '/vendor/tropotek',
        '/assets', '/plugin', '/theme'
    );

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
        $this->setDefaultMessage('~Auto: Commit');
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
        $this->output->writeln($this->commentOut('Dry Run Enabled.'), OutputInterface::VERBOSITY_NORMAL);
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
     * @param $message
     * @return $this
     */
    public function setDefaultMessage($message)
    {
        $this->defaultMessage = $message;
        return $this;
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
            throw new \Exception('This folder does not appear to be a GIT repository.');
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
     * getChangelog
     *
     * @return string
     */
    public function getChangelog()
    {
        return $this->changelog;
    }

    /**
     * Get the path for the most recent tag version
     *
     * @return string
     */
    public function getCurrentTag()
    {
        $tags = $this->getTagList();
        if (is_array($tags))
            return end($tags);
        return '';
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
    public function isDiff($tagName, $excludeFiles = array())
    {
        return count($this->diff($tagName, $excludeFiles));
    }

    /**
     * Commit the current branch and push to remote repos
     *
     * @param string $message
     * @throws \Exception
     * @return static
     */
    public function commit($message = '')
    {
        $this->cmdBuf = array();
        $lastLine = '';
        $ret = null;
        if (!$message) {
            $message = $this->defaultMessage;
        }

        $cmd = sprintf('git commit -am %s 2>&1 ', escapeshellarg($message));
        $this->output->writeln($this->infoOut($cmd), OutputInterface::VERBOSITY_NORMAL);
        if (!$this->isDryRun()) {
            $lastLine = exec($cmd, $this->cmdBuf, $ret);
            $this->output->writeln(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_NORMAL);
        }

        $this->cmdBuf = array();
        $cmd = sprintf('git push 2>&1 ');
        $this->output->writeln($this->infoOut($cmd), OutputInterface::VERBOSITY_NORMAL);
        if (!$this->isDryRun()) {
            $lastLine = exec($cmd, $this->cmdBuf, $ret);
            $this->output->writeln(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_NORMAL);
        }

        if ($ret) {     // TODO: check if this is the correct response here
            throw new \Exception('Cannot push branch: ' . $lastLine);
        }
        return $this;
    }

    /**
     * Commit the current branch and push to remote repos
     *
     * @throws \Exception
     */
    public function update()
    {
        $this->cmdBuf = array();
        $cmd = sprintf('git pull 2>&1 ');
        $this->output->writeln($this->infoOut($cmd), OutputInterface::VERBOSITY_NORMAL);
        $lastLine = exec($cmd, $this->cmdBuf, $ret);

        // TODO: Look for a nicer way to handle this
        //$this->output->writeln(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_NORMAL);
        if (count($this->cmdBuf) && $lastLine) {
            $out = implode("\n", $this->cmdBuf);
            if (preg_match('/error:/', $out)) {
                $this->output->writeln($out, OutputInterface::VERBOSITY_NORMAL);
            } else if (preg_match('/Already up-to-date/', $lastLine)) {
                $this->output->writeln('Already up-to-date', OutputInterface::VERBOSITY_NORMAL);
            } else if (preg_match('/([0-9]+) files? changed/', $lastLine, $reg)) {
                $this->output->writeln('  + ' . $reg[1] . ' files changed', OutputInterface::VERBOSITY_NORMAL);
            } else {

/*  TODO:
vd({array}): Array
(
    [0] => From https://github.com/tropotek/ems-ruleset
    [1] =>    f76116e..d320579  master     -> origin/master
    [2] => Updating f76116e..d320579
    [3] => Fast-forward
    [4] =>  Plugin.php                               | 10 +++++-
    [5] =>  Rs/Calculator.php                        |  2 --
    [6] =>  Rs/Db/Rule.php                           |  1 -
    [7] =>  Rs/Db/RuleMap.php                        | 19 +++++-----
    [8] =>  Rs/Listener/AssessmentUnitsHandler.php   | 61 ++++++++++++++++++++++++++++++++
    [9] =>  Rs/Listener/CategoryClassHandler.php     |  3 +-
    [10] =>  Rs/Listener/ProfileEditHandler.php       | 44 ++++++++++++++---------
    [11] =>  Rs/Listener/SetupHandler.php             |  1 +
    [12] =>  Rs/Listener/StudentAssessmentHandler.php | 12 +++----
    [13] =>  9 files changed, 114 insertions(+), 39 deletions(-)
    [14] =>  create mode 100644 Rs/Listener/AssessmentUnitsHandler.php
)
*/
                $this->output->writeln($out, OutputInterface::VERBOSITY_NORMAL);
            }
        }

        if ($ret) {
            throw new \Exception('Cannot update branch: ' . $lastLine);
        }
    }


    /**
     * Commit the current branch and push to remote repos
     *
     * @param string $branch
     * @throws \Exception
     */
    public function checkout($branch = 'master')
    {
        $this->cmdBuf = array();
        $cmd = sprintf('git checkout %s 2>&1 ', escapeshellarg($branch));
        $this->output->writeln($this->infoOut($cmd), OutputInterface::VERBOSITY_NORMAL);
        $lastLine = exec($cmd, $this->cmdBuf, $ret);
        $this->output->writeln(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_NORMAL);

        if ($ret) {
            throw new \Exception('Cannot checkout branch: ' . $lastLine);
        }
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
            $this->output->writeln($this->infoOut($cmd), OutputInterface::VERBOSITY_NORMAL);
            exec($cmd, $this->cmdBuf);
            $this->output->writeln(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_NORMAL);

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
            $this->output->writeln($this->infoOut($cmd), OutputInterface::VERBOSITY_NORMAL);
            exec($cmd, $this->cmdBuf);
            $this->output->writeln(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_NORMAL);

            foreach($this->cmdBuf as $line) {
                $line = trim($line);
                if (!$line) continue;
                if (preg_match('/^([0-9\.]+)/i', $line, $regs)) {
                    $this->tagList[$line] = $line;
                }
            }
            \Tbx\Utils::sortVersionArray($this->tagList);
        }
        return $this->tagList;
    }


    /**
     * return a list of changed files with out the excluded files.
     *
     * @param string $tagName
     * @param array  $excludeFiles
     * @return array
     */
    public function diff($tagName, $excludeFiles = array())
    {
        if ($tagName == '0.0.0') {
            return array('Tagged initial project');
        }
        $this->cmdBuf = array();
        $tagName = trim($tagName, '/');
        $cmd = 'git diff --name-status 2>&1 '.escapeshellarg($tagName).' HEAD';
        $this->output->writeln($this->infoOut($cmd), OutputInterface::VERBOSITY_NORMAL);
        exec($cmd, $this->cmdBuf);
        $this->output->writeln(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_NORMAL);

        $changed = array();
        foreach($this->cmdBuf as $line) {
            if (!preg_match('/^[a-z]\s+(\S+)/i', $line, $regs)) {
                continue;
            }
            if (in_array(trim($regs[1]), $excludeFiles)) {
                continue;
            }
            $changed[] = trim($regs[1]);
        }
        $this->output->writeln($this->commentOut($changed), OutputInterface::VERBOSITY_NORMAL);
        return $changed;
    }



    

    /**
     * Get an array of changes to the tag since the last copy command was executed.
     *
     * @param string $version
     * @return array
     */
    public function makeChangelog($version)
    {
        $exists = array();
        $logs = array();

        $cmd = sprintf('git log --oneline %s..HEAD 2>&1 ', escapeshellarg($version));
        $this->output->writeln($this->infoOut($cmd), OutputInterface::VERBOSITY_NORMAL);
        exec($cmd, $this->cmdBuf, $ret);
        $this->output->writeln(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_NORMAL);
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
                if (strlen($msg) <= 2 || preg_match('/^~?Auto/', $msg)) {
                    $this->output->writeln($this->commentOut('  $msg(-) => ' . $msg), OutputInterface::VERBOSITY_VERBOSE);
                    continue;
                } else {
                    $this->output->writeln($this->commentOut('  $msg(+) => ' . $msg), OutputInterface::VERBOSITY_VERBOSE);
                }
                if (!in_array(md5($msg), $exists)) {
                    $logs[] = $msg;
                    $exists[] = md5($msg);
                }
            }
        }
        return $logs;
    }


    /**
     * Tag a new release, basically copy the release to a tag folder
     * Returns true if a new tag was created, false if not.
     *
     * @param string $version A version string in the format of x.x.x
     * @param string $message Any commit message, if non supplied the version will be used
     * @return boolean
     * @throws \Exception
     */
    public function tagRelease($version, $message = '')
    {
        if (!$message) {
            $message = 'Tagging new release: '.$version;
        }

        $json = file_get_contents('composer.json');
        if ($json) {
            $jsonTag = json_decode($json);
            $jsonTag->version = $version;
            $jsonTag->time = date('Y-m-d');
            file_put_contents('composer.json', \Tbx\Utils::jsonPrettyPrint(json_encode($jsonTag)));
            $this->commit();
        }

        $logArr =  $this->makeChangelog($this->getCurrentTag());
        $log = '';
        if (is_array($logArr)) {
            $this->changelog = sprintf("Ver %s [%s]:\n-------------------------------\n", $version, date('Y-m-d'));
            foreach ($logArr as $line) {
                if (str_word_count($line) <= 1)
                    continue;
                $this->changelog .= '' . wordwrap(ucfirst($line), 100, "\n   ") . "\n";
            }
            $log = file_get_contents('changelog.md');
            if ($log && $this->changelog && !preg_match('/Ver\s+'.preg_quote($version).'\s+\[[0-9]{4}\-[0-9]{2}\[0-9]{2}\]/i', $this->changelog)) {
                $logTag = '#CHANGELOG#';
                $changelog = $logTag . "\n\n" . $this->changelog;
                $log = str_replace($logTag, $changelog, $log);
            }
            $this->output->writeln($log, OutputInterface::VERBOSITY_VERY_VERBOSE);
        }


        // Copy log
        if ($log && $this->changelog) {
            $this->output->writeln('  Updating changelog.md.', OutputInterface::VERBOSITY_NORMAL);
            if (!$this->isDryRun()) {
                file_put_contents('changelog.md', $log);
            }
            $this->commit();
        }
        $this->cmdBuf = array();

        // Tag trunk
        $cmd = sprintf("git tag -a %s -m %s 2>&1 ", $version, escapeshellarg($message) );
        $this->output->writeln($this->infoOut($cmd), OutputInterface::VERBOSITY_NORMAL);
        if (!$this->isDryRun()) {
            exec($cmd, $this->cmdBuf);
            $this->output->writeln(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_NORMAL);
        }
        $this->cmdBuf = array();
        $cmd = sprintf("git push --tags");
        $this->output->writeln($this->infoOut($cmd), OutputInterface::VERBOSITY_NORMAL);
        if (!$this->isDryRun()) {
            exec($cmd, $this->cmdBuf);
            $this->output->writeln(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_NORMAL);
        }

        // Restore trunk composer.json
        if ($json) {
            $this->output->writeln('  Updating composer.json', OutputInterface::VERBOSITY_NORMAL);
            if (!$this->isDryRun()) {
                file_put_contents('composer.json', $json);
            }
            $this->commit();
        }

        return true;
    }


    /**
     * Get the current branch
     */
    public function getCurrentBranch()
    {
        $cmd = sprintf('git branch');
        $this->output->writeln($this->infoOut($cmd), OutputInterface::VERBOSITY_NORMAL);
        exec($cmd, $this->cmdBuf);
        $this->output->writeln(implode("\n", $this->cmdBuf), OutputInterface::VERBOSITY_NORMAL);
        foreach($this->cmdBuf as $line) {
            if (preg_match('/^\* (b[0-9]+\.[0-9]+\.[0-9]+)/', $line, $regs)) {
                return $regs[1];
            }
        }
        return 'master';
    }





    protected function infoOut($str)
    {
        return sprintf('<info>%s</info>', $str);
    }

    protected function commentOut($str)
    {
        return sprintf('<comment>%s</comment>', $str);
    }

    protected function questionOut($str)
    {
        return sprintf('<question>%s</question>', $str);
    }

    protected function errorOut($str)
    {
        return sprintf('<error>%s</error>', $str);
    }

}