<?php
/**
 * Created by PhpStorm.
 * User: mifsudm
 * Date: 1/30/14
 * Time: 8:58 AM
 */

namespace Tbx\Vcs\Adapter;

/**
 * Class Git
 * Use this to do operations on an Github repository
 *
 *
 * @package Tbx\Vcs\Adapter
 */
class Git extends Iface
{


    /**
     * Checkout the master/trunk repository to a tmp folder
     *
     * @return string
     */
    public function checkout()
    {
        $this->output = '';
        if (!is_dir($this->getTmpDir().'/master')) {
            if (!is_dir($this->tmp)) {
                mkdir($this->tmp);
            }
            $cmd = sprintf('git clone %s %s', escapeshellarg($this->makeUri()), escapeshellarg($this->getTmpDir().'/master'));
            exec($cmd, $this->output);
            $this->output = implode("\n", $this->output);
        }
        return $this->output;
    }

    /**
     * Commit the master/trunk repository to a tmp folder
     *
     * @param string $message Any commit message, if non supplied the version will be used
     * @return string
     */
    public function commit($message = '')
    {
        $this->output = '';
        if (!$this->isDryRun()) {
            if ($message) {
                $message = escapeshellarg($message);
            } else {
                $message = '\'Auto Commit\'';
            }
            $cmd = sprintf('cd %s && git commit -am %s ', escapeshellarg($this->getTmpDir().'/master'), $message);
            exec($cmd, $this->output);
            $this->output = implode("\n", $this->output);

            // Test this, but I think its correct....
            $cmd = sprintf('cd %s && git push ', escapeshellarg($this->getTmpDir().'/master') );
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
        if (!$this->tagList || $force) {
            $cmd = 'git ls-remote --tags ' . $this->getUri();
            exec($cmd, $out);
            $this->tagList = array();
            foreach($out as $line) {
                if (!$line) continue;
                if (preg_match('/^([0-9a-f]{40})\s+(\S+)/i', $line, $regs)) {
                    $line = basename($regs[2]);
                    if (preg_match('/^[0-9]/', $line)) {
                        $this->tagList[$regs[1]] = $line;
                    }
                }
            }
            $this->sortVersionArray($this->tagList);
        }
        return $this->tagList;
    }

    /**
     * Get the file contents from a repository file.
     *
     * @param string $path This is a relative path from the base repository URI
     * @return string
     */
    public function getFileContents($path)
    {
        if ($path[0] != '/') {
            $path = '/'.$path;
        }
        if (is_file($this->getTmpDir().'/master'.$path))
            return file_get_contents($this->getTmpDir().'/master'.$path);
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
        if (!$this->isDryRun()) {
            return file_put_contents($this->getTmpDir().'/master'.$path, $str);
        }
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
     * @param array $excludeFiles (GIT Unused) All files must have the initial / removed as it is assumed relative to the project.
     * @throws \Exception
     * @return boolean
     */
//     public function isDiff_old($tagName, $excludeFiles = array('composer.json'))
//    {
//
//
//        return false;
//
//
//
//
//        $out = array();
//        $cmd = 'git ls-remote ' . $this->getUri();
//        exec($cmd, $out);
//        $masterId = '';
//        vd($out);
//        foreach($out as $i => $line) {
//            if (preg_match('/\^\{\}$/', $line)) continue;
//            if (preg_match('/^([0-9a-f]{40})\s+(\S+)/i', $line, $regs)) {
//                vd('1', $regs);
//                if ($i == 0) {
//                    $masterId = $regs[1];
//                    continue;
//                }
//                $tag = basename($regs[2]);
//                if (preg_match('/^[0-9]/', $tag) && $tagName == $tag) {
//                    vd('2', $regs);
//                    if ($masterId == $regs[1]) {
//                        return false;
//                    }
//                    break;
//                }
//            }
//        }
//        vd('Release', $masterId);
//        return true;
//    }

    /**
     *
     *
     *
     * @param string $tagName
     * @param array  $excludeFiles
     * @return array
     */
    public function diff($tagName, $excludeFiles = array('composer.json', 'changelog.md'))
    {
        $this->output = '';
        $tagName = trim($tagName, '/');
        $cmd = 'git ls-remote ' . $this->getUri();
//        $cmd = "git diff HEAD ".escapeshellarg($tagName)." --minimal | grep '^diff --git '";
        if ($this->isDryRun()) {
            echo ' = ' . $cmd . "\n";
        }
        exec($cmd, $this->output);

        // TODO: Cause git does not have a remote diff command, we have to just fake it.
        // This means that tags will be created every time this command is run regardless of
        // any changes in the trunk.

        // We only want this to return a list of changed files so that tags are created only when the repo
        // has updates and is not the same as an existing tag.
        $changed = array('release.md');
        foreach($this->output as $line) {
            ;
        }


        //TODO:

        return $changed;
    }


    /**
     * Get an array of changes to the tag since the last copy command was executed.
     *
     * @param $path
     * @return array
     * @todo: See why this command does not work.
     */
    public function makeChangelog($path = 'master')
    {
        $cmd = sprintf('git log -n 20 --format=oneline %s %s', escapeshellarg($path), escapeshellarg($this->getCurrentTag()));
        exec($cmd, $list, $ret);
        if ($ret) {
            return false;
        }
        $exists = array();
        $logs = array();
        foreach ($list as $i => $log) {
            $msg = $log;
            if (!preg_match('/^([0-9a-f]{40})\s+(.+)/i', $msg, $regs)) {
                continue;
            }
            $msg = trim($regs[2]);
            if (strlen($msg) <= 2 || preg_match('/^Auto /i', $msg)) {
                continue;
            }
            if (!in_array(md5($msg), $exists)) {
                $logs = array_merge($logs, explode("\n", $msg));        // << Not sure this is needed fro git, multi line logs won't exist.
                $exists[] = md5($msg);
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
     */
    public function tagRelease($version, $message = '')
    {
        if (!$message) {
            $message = 'Tagging a new release: '.$version;
        }

        $json = $this->getFileContents('/composer.json');
        if ($json) {
            $jsonTag = json_decode($json);
            $jsonTag->version = $version;
            $jsonTag->time = date('Y-m-d');
            $this->setFileContents('/composer.json', jsonPrettyPrint(json_encode($jsonTag)));
            $this->commit();
        }

        $logArr =  $this->makeChangelog();
        $log = '';
        if (is_array($logArr)) {
            $this->changelog = sprintf("Ver %s [%s]:\n----------------\n", $version, date('Y-m-d'));
            foreach ($logArr as $line) {
                if (str_word_count($line) <= 1)
                    continue;
                $this->changelog .= " - " . wordwrap(ucfirst($line), 100, "\n   ") . "\n";
            }
            $log = $this->getFileContents('changelog.md');
            if ($log && $this->changelog) {
                $logTag = '#CHANGELOG#';
                $changelog = $logTag . "\n\n" . $this->changelog;
                $log = str_replace($logTag, $changelog, $log);
            }
        }

        // Tag trunk
        $cmd = sprintf("cd %s && git tag -a %s -m %s", $this->getTmpDir() . '/master', $version, escapeshellarg($message) );
        $this->output = $cmd;
        if (!$this->isDryRun()) {

            // Copy log
            if ($log && $this->changelog) {
                $this->setFileContents('changelog.md', $log);
                $this->commit();
            }
            exec($cmd, $this->output);
            $this->output = implode("\n", $this->output);

            $pushTag = sprintf("cd %s && git push --tags", $this->getTmpDir() . '/master');
            exec($pushTag, $this->output);
            $this->output = implode("\n", $this->output);

            // Restore trunk composer.json
            if ($json) {
                $this->setFileContents('/composer.json', $json);
                $this->commit();
            }
        }
        return $this->output;
    }
//mifsudm@252s-dev:~/public_html/Unimelb$ git ls-remote https://github.com/tropotek/tk-installers.git
//436f5dda4395b32fff81ee3b555be493e82f970c        HEAD
//436f5dda4395b32fff81ee3b555be493e82f970c        refs/heads/master
//1a171437273291ef8496c31cd8b98ad1b24b6212        refs/tags/1.2.0
//9cc3722e58adaf7998f490f3ab2fa6a2d52d8360        refs/tags/1.2.0^{}


//mifsudm@252s-dev:~/public_html/Unimelb$ git ls-remote https://github.com/tropotek/tk-installers.git
//af964ca433e1bdf086464bbd4f1c470343d76f64        HEAD
//af964ca433e1bdf086464bbd4f1c470343d76f64        refs/heads/master
//1a171437273291ef8496c31cd8b98ad1b24b6212        refs/tags/1.2.0
//9cc3722e58adaf7998f490f3ab2fa6a2d52d8360        refs/tags/1.2.0^{}
//8fc036421fb7e7e29c088e7fb0d5167fdcf7423d        refs/tags/1.2.1
//75d2d0ae625be6efb54666fe1feec581ae708884        refs/tags/1.2.1^{}



    /**
     * Returns true if the path is a file
     *
     * @param string $path This is a relative path from the base repository URI
     * @return boolean
     */
    public function isFile($path)
    {
        if ($path[0] != '/') {
            $path = '/'.$path;
        }
        return is_file($this->getTmpDir().'/master'.$path);
    }

    /**
     * Returns true if the path is a directory
     *
     * @param string $path This is a relative path from the base repository URI
     * @return boolean
     */
    public function isDir($path)
    {
        if ($path[0] != '/') {
            $path = '/'.$path;
        }
        return is_dir($this->getTmpDir().'/master'.$path);
    }
}