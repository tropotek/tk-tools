<?php
namespace Tbx\Vcs\Adapter;

/**
 * Use this to do operations on an SVN repository
 *
 * NOTE: implement this SVN Adapter one-day if we can use it??????
 * @todo no longer in a working state, need to fix one day....???
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Svn extends Iface
{

    /**
     * Checkout the master/trunk repository to a tmp folder
     *
     * @return string
     */
    public function checkout()
    {
        $this->output = '';
        if (!is_dir($this->getTmpDir().'/trunk')) {
            if (!is_dir($this->workingDirectory)) {
                mkdir($this->workingDirectory);
            }
            $cmd = sprintf('svn co %s %s ', escapeshellarg($this->makeUri('/trunk')), escapeshellarg($this->getTmpDir().'/trunk'));
            exec($cmd, $this->output);
            $this->output = implode("\n", $this->output);
        }
        return $this->output;
    }

    /**
     * Commit the master/trunk repository to a tmp folder
     *
     * @param string $message
     * @return string
     */
    public function commit($message = '')
    {
        $this->output = '';
        if (!$this->isDryRun()) {
            $cmd = sprintf('svn ci -m %s %s ', escapeshellarg($message), escapeshellarg($this->getTmpDir().'/trunk') );
            exec($cmd, $this->output);
            $this->output = implode("\n", $this->output);
        }
        return $this->output;
    }

    /**
     * Get an array of current tagged versions.
     *
     * @param bool $force If true the tag list will be refreshed from the repository
     * @return array
     */
    public function getTagList($force = false)
    {
        $this->output = '';
        if (!$this->tagList || $force) {
            exec('svn ls --xml ' . $this->makeUri('/tags'), $this->output);
            $xml = implode("\n", $this->output);
            $xobj = simplexml_load_string($xml);
            $this->tagList = array();
            foreach ($xobj->list->entry as $entry) {
                $this->tagList[] = $entry->name.'';
            }
            $this->sortVersionArray($this->tagList);
        }
        return $this->tagList;
    }

    /**
     * Get the file contents from a repository file.
     *
     * @param string $path This is a relative path from the trunk/master repository URI
     * @return string
     */
    public function getFileContents($path)
    {
        if ($path[0] != '/') {
            $path = '/'.$path;
        }
        return @file_get_contents($this->getTmpDir().'/trunk'.$path);
    }

    /**
     * Set the file contents from a repository file.
     *
     * @param string $path This is a relative path from the trunk/master repository URI
     * @param string $str The file contents to put
     * @return string
     */
    public function setFileContents($path, $str)
    {
        if ($path[0] != '/') {
            $path = '/'.$path;
        }
        return file_put_contents($this->getTmpDir().'/trunk'.$path, $str);
    }

    /**
     * Returns true if the $cmpPath and $srcPath are different
     * IE: have modifications.
     *
     * This can be used to make decisions based on if the two tags
     * have had any modifications, ie: like releasing a version if
     * changes have been committed or not.
     *
     * @param string $tagName The tag/version name of the tag folder
     * @param array $excludeFiles All files must have the initial / removed as it is assumed relative to the project.
     * @return integer
     */
    public function diff($tagName, $excludeFiles = array('composer.json', 'changelog.md'))
    {
        $this->output = '';
        $tagName = trim($tagName, '/');
        $cmd = sprintf('svn diff --summarize --xml %s %s', $this->makeUri('/trunk'), $this->makeUri('/tags/'.$tagName));
        if ($this->isDryRun()) {
            echo ' = ' . $cmd . "\n";
        }
        exec($cmd, $this->output);
        // Exclude files from diff, user project relative paths
        $xml = implode("\n", $this->output);
        $xobj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
        $files = (array) $xobj->paths->path;
        $changed = array();
        foreach ($files as $k => $file) {
            if ($k == '@attributes') continue;
            $file = $file.'';
            $file = str_replace($this->makeUri('/trunk').'/', '', $file);
            if (!in_array($file, $excludeFiles)) {
                $changed[] = $file;
            }
        }
        return $changed;
    }

    /**
     * Tag a new release, basically copy the release to a tag folder
     * Returns true if a new tag was created, false if not.
     *
     * The svn command results are sent to stdout during runtime.
     *
     * @param string $version A version string in the format of x.x.x
     * @param string $message Any commit message, if non supplied the version will be used
     * @return boolean
     */
    public function tagRelease($version, $message = '')
    {
        $this->output = '';
        if (!$message) {
            $message = 'Tagging a new release: '.$version;
        }

        $date = $this->getTagDate();

        $json = $this->getFileContents('/composer.json');
        if ($json) {
            $jsonTag = json_decode($json);
            $jsonTag->version = $version;
            $jsonTag->time = $date->format('Y-m-d');
            $this->setFileContents('/composer.json', jsonPrettyPrint(json_encode($jsonTag)));
            //$this->commit('tag: Updated composer.json for tag release');
            $this->commit();
        }

        $logArr =  $this->makeChangelog();

        $this->changelog  = sprintf("Ver %s [%s]:\n----------------\n", $version, $date->format('Y-m-d'));
        foreach ($logArr as $line) {
            if (str_word_count($line) <= 1) continue;
            $this->changelog .= " - " . wordwrap(ucfirst($line), 100, "\n   ") . "\n";
        }

        $log = $this->getFileContents('changelog.md');
        if ($log && $this->changelog) {
            $logTag = '#CHANGELOG#';
            $changelog = $logTag . "\n\n" . $this->changelog;
            $log = str_replace($logTag, $changelog, $log);
        }

        // Tag trunk
        $out = '';
        if (!$this->isDryRun()) {
            // Copy log
            if ($log && $this->changelog) {
                $this->setFileContents('changelog.md', $log);
                $this->commit();
            }

            $src = $this->getUri() . '/trunk';
            $dst = $this->getUri() . '/tags/' . $version;
            $cmd = sprintf("svn cp -m '%s' %s %s", $message, $src, $dst);
            exec($cmd, $this->output);
            $this->output = implode("\n", $this->output);

            if ($json) {
                // Restore trunk composer.json
                $this->setFileContents('/composer.json', $json);
                $this->commit();
            }
        }
        return  $this->output;
    }

    /**
     * Get an array of changes from the last most recent tag.
     *
     * @param $path
     * @return array
     */
    public function makeChangelog($path = '/trunk')
    {
        $cmd = sprintf('svn log -v -l 20 --stop-on-copy --xml %s', $this->makeUri($path));
        exec($cmd, $output);
        $xml = implode("\n", $output);
        $xobj = simplexml_load_string($xml);
        if (!$xobj) return array();
        $list = $xobj->logentry;    // Check what happens on single item (ie: not an array)
        $logs = array();
        $exists = array();
        foreach ($list as $i => $log) {
            $msg = trim($log->msg.'');
            if ($msg == '' || preg_match('/^auto commit from .+$/i', $msg) || $msg == '--novendor' || strlen($msg) <= 2) {
                continue;
            }
            if (!in_array(md5($msg), $exists)) {
                if (strstr($msg, "\n")) {
                    $logs = array_merge($logs, explode("\n", $msg));
                } else {
                    $logs = array_merge($logs, explode('\n', $msg));
                }
                $exists[] = md5($msg);
            }
        }
        return $logs;
    }




    /**
     * Get the date of the first log in the tag and use it as the release date.
     *
     * @param $path
     * @return \DateTime
     */
    public function getTagDate($path = '/trunk')
    {
        $cmd = sprintf('svn log -v --stop-on-copy --xml %s', $this->makeUri($path));
        exec($cmd, $output);
        $xml = implode("\n", $output);
        $xobj = simplexml_load_string($xml);
        if ($xobj && $xobj->logentry) {
            $log = $xobj->logentry[count($xobj->logentry)-1];
            if ($log && $log->date) {
                return new \DateTime($log->date);
            }
        }
        return new \DateTime();
    }


    /**
     * Returns true if the path is a file
     *
     * @param string $path This is a relative path from the trunk/master repository URI
     * @return boolean
     */
    public function isFile($path)
    {
        if ($path[0] != '/') {
            $path = '/'.$path;
        }
        return is_file($this->getTmpDir().'/trunk'.$path);
    }

    /**
     * Returns true if the path is a directory
     *
     * @param string $path This is a relative path from the trunk/master repository URI
     * @return boolean
     */
    public function isDir($path)
    {
        if ($path[0] != '/') {
            $path = '/'.$path;
        }
        return is_dir($this->getTmpDir().'/trunk'.$path);
    }

    /**
     * Get the repository package base URI
     *
     * @return string
     */
    public function getUri()
    {
        // TODO: Implement getUri() method.
    }

    /**
     * Update the current checked out branch
     *
     * @return $this
     */
    public function update()
    {
        // TODO: Implement update() method.
    }
}