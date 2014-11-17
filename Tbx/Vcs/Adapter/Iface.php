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
 * @package Tbx\Vcs\Adapter
 */
abstract class Iface
{

    /**
     * The repository base URI, all paths used should
     * be prepended with this base uri.
     * @var string
     */
    private $uri = '';

    /**
     * The temp directory to co the trunk
     * @var string
     */
    protected $tmp = '';

    /**
     * If true nothing is committed to the repository
     * @var boolean
     */
    protected $dryRun = false;

    /**
     * If true nothing is committed to the repository
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
     * Constructor
     *
     * @param string $trunkUri
     * @param string $tmpBaseDir
     * @param bool $dryRun
     * @param bool $noClean
     * @throws \Exception
     */
    public function __construct($trunkUri, $tmpBaseDir = '/tmp', $dryRun = false, $noClean = false)
    {
        rtrim($trunkUri, '/');
        if (!preg_match('/^[a-z0-9]{2,8}:\/\/(www\.)?[\S]+$/i', $trunkUri)) {
            throw new \Exception('Invalid base URI supplied for VCS repository');
        }
        if (preg_match('/\/trunk$/', $trunkUri)) {
            $trunkUri = substr($trunkUri, 0, 6);
        }
        $this->uri      = $trunkUri;
        $this->dryRun   = $dryRun;
        $this->noClean  = $noClean;
        $this->tmp      = $tmpBaseDir.'/tkTag_'.basename($trunkUri);

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
     * cleanup folders
     */
    public function __destruct()
    {
        if (!$this->noClean) {
            exec('rm -rf ' . escapeshellarg($this->tmp), $this->output);
        }
    }

    /**
     * Get the repository checked out tmp folder
     *
     * @return string
     */
    public function getTmpDir()
    {
        return $this->tmp;
    }

    /**
     * Get the repository package base URI
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
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
     * Prepend the repository uri path to the supplied path.
     *
     * @param string $path The relative path from the base repository URI
     * @throws \Exception
     * @return string
     */
    protected function makeUri($path = '')
    {
        $path = str_replace($this->getUri(), '', $path);
        if ($path && $path[0] != '/') {
            throw new \Exception('Path must start with a /: ' . $path);
        }
        $path = rtrim($path, '/');
        return $this->getUri() . $path;
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
     * @param string $currVersion The current version to increment
     * @param string $maskVersion The proposed mask version. Default 0.0.x
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
     * Get an array of changes to the tag since the last copy command was executed.
     *
     * @param $path
     * @return array
     */
    abstract public function makeChangelog($path = '/trunk');



    /**
     * Checkout the master/trunk repository to a tmp folder
     *
     * @return array
     */
    abstract public function checkout();


    /**
     * Commit the master/trunk repository to a tmp folder
     *
     * @param string $message
     * @return mixed
     */
    abstract public function commit($message = '');


    /**
     * Get an array of current tagged versions.
     *
     * @param bool $force If true the tag list will be refreshed from the repository
     * @return array
     */
    abstract public function getTagList($force = false);

    /**
     * Get the file contents from a repository file.
     *
     * @param string $path This is a relative path from the base repository URI
     * @return string
     */
    abstract public function getFileContents($path);

    /**
     * Set the file contents from a repository file.
     *
     * @param string $path This is a relative path from the trunk/master repository URI
     * @param string $str The file contents to put
     * @return string
     */
    abstract public function setFileContents($path, $str);

    /**
     * Return a list of modified files from the head/master tag
     *
     * @param string $tagName The tag/version name of the tag folder
     * @param array $excludeFiles All files must have the initial / removed as it is assumed relative to the project.
     * @return array
     */
    abstract public function diff($tagName, $excludeFiles);

    /**
     * Returns true if the tag is different than the head
     * IE: master/head has modifications since last release.
     *
     * This can be used to make decisions based on if the two tags
     * have had any modifications, ie: like releasing a version if
     * changes have been committed or not.
     *
     * @param string $tagName The tag/version name of the tag folder
     * @param array $excludeFiles All files must have the initial / removed as it is assumed relative to the project.
     * @return integer
     */
    public function isDiff($tagName, $excludeFiles = array('composer.json', 'changelog.md'))
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

    /**
     * Returns true if the path is a file
     *
     * @param string $path This is a relative path from the base repository URI
     * @return boolean
     */
    abstract public function isFile($path);

    /**
     * Returns true if the path is a directory
     *
     * @param string $path This is a relative path from the base repository URI
     * @return boolean
     */
    abstract public function isDir($path);


} 