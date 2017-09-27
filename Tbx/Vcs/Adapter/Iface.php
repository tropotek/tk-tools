<?php
/**
 * Created by PhpStorm.
 * User: mifsudm
 * Date: 1/30/14
 * Time: 8:28 AM
 */

namespace Tbx\Vcs\Adapter;

/**
 * Interface VCS Iface
 *
 * NOTE: Make sure the $tmp directory has been created or exists
 * before checking out a repository
 *
 */
abstract class Iface
{
    const LOG_CMD = 2;
    const LOG_DEBUG = 5;

    const LOG = 0;
    const LOG_V = 1;
    const LOG_VV = 3;
    const LOG_VVV = 5;




    /**
     * The repository base URI, all paths used should
     * be prepended with this base uri.
     * @var string
     */
    protected $uri = '';

    /**
     * The directory to the working copy trunk/master
     * @var string
     */
    protected $workingDirectory = '';

    /**
     * If true nothing is committed to the repository
     * @var boolean
     */
    protected $dryRun = false;

    /**
     * @var boolean
     */
    protected $noClean = false;

    /**
     * Command output strings will be placed in here
     * @var array
     */
    protected $output = null;

    /**
     * @var string
     */
    protected $changelog = '';

    /**
     * @var array
     */
    protected $tagList = null;

    /**
     * The verbosity level of the system. (0-5)
     * 0 = none, system msgs only
     * ...
     * 5 = all debug and system messages
     *
     * @var int
     */
    protected $verbose = 0;


    /**
     * Constructor
     *
     * @param bool   $dryRun
     * @throws \Exception
     */
    public function __construct($dryRun = false)
    {
        $this->dryRun = $dryRun;
        $this->workingDirectory = getcwd();
    }


    /**
     *
     *
     * @return string
     */
    public function getCmdPrepend()
    {
        if ($this->dryRun) {
            return 'DR=> ';
        }
        return '';
    }

    /**
     * Set the verbosity level of the system. (0-5)
     * 0 = none, system msgs only
     * ...
     * 5 = all debug and system messages
     *
     * @param $i
     * @return $this
     */
    public function setVerbose($i)
    {
        $this->verbose = $i;
        if ($this->verbose > 5) $this->verbose = 5;
        if ($this->verbose < 0) $this->verbose = 0;
        return $this;
    }

    /**
     *
     * @param $workingDirectory
     * @return $this
     * @throws \Exception
     */
    public function setWorkingDirectory($workingDirectory)
    {
        $workingDirectory = rtrim($workingDirectory, '/');
        if (!is_dir($workingDirectory.'/.git')) {
            throw new \Exception('Not a GIT repository');
        }
        $this->workingDirectory = $workingDirectory;
        return $this;
    }

    /**
     * Get the last command output string array
     *
     * @return array
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Get the repository checked out tmp folder
     *
     * @return string
     */
    public function getWorkingDirectory()
    {
        return $this->workingDirectory;
    }

    /**
     * Is this a dry run.
     * No commits should occur if value is true.
     *
     * @return bool
     */
    public function isDryRun()
    {
        return $this->dryRun;
    }

    /**
     * Compare a current version to a version mask and return
     * the next version number.
     *
     * Increments the patch $currentVersion by the $step value
     * EG:
     *  o 1.3.9 will become 1.3.10 if no other params are supplied.
     *  o If a $maskVersion of 2.1.x is supplied with a $currentVersion
     *    of 1.3.9 then the result will be 2.1.0
     *  o If a $maskVersion of 1.3.x is supplied with a $currentVersion
     *    of 1.3.9 then the result will be 1.3.10
     *
     * @param string  $currVersion The current version to increment
     * @param string  $maskVersion The proposed mask version. Default 0.0.x
     * @param integer $step The number to increment the version by. Default 1
     * @return string
     */
    public function incrementVersion($currVersion, $maskVersion = '0.0.x', $step = 1)
    {
        preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)$/', $currVersion, $currParts);
        preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)$/', $maskVersion, $maskParts);
        if (count($maskParts) && version_compare($currParts[1] . '.' . $currParts[2], $maskParts[1] . '.' . $maskParts[2], '<')) {
            return $maskParts[1] . '.' . $maskParts[2] . '.0';
        }
        $ver = $currParts[1] . '.' . $currParts[2] . '.' . ($currParts[3] + $step);
        return $ver;
    }

    /**
     * Sort an array of software versions....
     *
     * @see http://php.net/version_compare
     * @param array $array
     * @return bool true on success or false on failure.
     */
    public function sortVersionArray(&$array)
    {
        return usort($array, function ($a, $b) {
            if ($a == $b) {
                return 0;
            } else if (version_compare($a, $b, '<')) {
                return -1;
            } else {
                return 1;
            }
        });
    }

    /**
     * Get the path for the most recent tag version
     *
     * @param bool $force If true the tag list will be refreshed from the repository
     * @return string
     */
    public function getCurrentTag($force = false)
    {
        $tags = $this->getTagList($force);
        if (is_array($tags))
            return end($tags);
        return '';
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
     * Echo a message based on set verbosity...
     *
     * @param string $msg
     * @param int $verbose
     * @return $this
     */
    public function log($msg, $verbose = 1)
    {
        if ($this->verbose >= $verbose) {
            if (is_object($msg) || is_array($msg)) {
                $msg = print_r($msg, true);
            }
            // style command messages
            if ($verbose == self::LOG_CMD && !strstr($msg, "\n")) {
                $msg = '  $ ' . $msg;
            }
            echo $msg . "\n";
        }
        return $this;
    }


    /**
     * Get the repository package base URI
     *
     * @return string
     */
    abstract public function getUri();

    /**
     * Get an array of changes to the tag since the last
     * tagged release...
     *
     * @param string $version
     * @return array
     */
    abstract public function makeChangelog($version);


    /**
     * Commit the current checked out branch
     *
     * @param string $message
     * @return $this
     */
    abstract public function commit($message = '');


    /**
     * Update the current checked out branch
     *
     * @return $this
     */
    abstract public function update();


    /**
     * Get an array of current tagged versions.
     *
     * @return array
     */
    abstract public function getTagList();

    /**
     * Return a list of modified files from the head/master tag
     *
     * @param string $tagName The tag/version name of the tag folder
     * @param array  $excludeFiles All files must have the initial / removed as it is assumed relative to the project.
     * @return array
     */
    abstract public function diff($tagName, $excludeFiles = array());

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
     * Tag a new release, basically copy the release to a tag folder
     * Returns true if a new tag was created, false if not.
     *
     * @param string $version A version string in the format of x.x.x
     * @param string $message Any commit message, if non supplied the version will be used
     * @return boolean
     */
    abstract public function tagRelease($version, $message = '');



} 