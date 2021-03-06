<?php
namespace Tbx;


/**
 * This object contains helpful functions that execute commands
 * in the shell. Developed mainly for scripts to be run on
 * linux servers.
 *
 * @notes If using windows/ios this script may need to be modified. *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class Console
{

    /**
     * Compress the package src files into an archive file
     * Using tgz, or zip
     *
     * @param string $src
     * @param string $dst
     * @param string $type one of zip of tgz
     * @return string
     */
    public static function compress($src, $dst, $type = 'tgz')
    {
        try {
            if (!$type || $type == 'tgz') {
                // TODO: Fix `file changed as we read it` error
                $cmd = sprintf("cd %s && tar zcf %s ./  --exclude='%s'", escapeshellarg($src), escapeshellarg($dst), escapeshellarg($dst));
            } else {
                $cmd = sprintf("cd %s && zip -y -r -9 %s ./", escapeshellarg($src), escapeshellarg($dst));
            }
            self::exec($cmd);
        } catch (\Exception $e) {}

        $cmd = sprintf("mv %s %s", escapeshellarg($src.'/'.$dst), escapeshellarg(dirname($src) . '/' . $dst));
        self::exec($cmd);
        return dirname($src) . '/' . $dst;
    }

    /**
     * svnFile
     *
     * @param array $file
     * @return string
     */
    public static function svnExport($svnUri, $dst)
    {
        $svnUri = escapeshellarg($svnUri);
        $dst = escapeshellarg($dst);
        return self::exec(sprintf("svn export %s %s --force --non-interactive", $svnUri, $dst));
    }

    /**
     * copy a file
     *
     * @param string $src
     * @param string $dst
     * @return string
     */
    public static function cpFile($src, $dst)
    {
        if (file_exists($src)) {
            $src = escapeshellarg($src);
            $dst = escapeshellarg($dst);
            return self::exec(sprintf("cp -R %s %s", $src, $dst));
        }
        return '';
    }

    /**
     * Symbolic link a file
     *
     * @param string $src
     * @param string $dst
     * @return string
     */
    public static function lnFile($src, $dst)
    {
        $src = escapeshellarg($src);
        $dst = escapeshellarg($dst);
        return self::exec(sprintf("ln -s %s %s", $src, $dst));
    }

    /**
     * delete a file
     *
     * @param string $file
     * @return string
     */
    public static function rm($file)
    {
        $file = escapeshellarg($file);
        return  self::exec(sprintf("rm -rf %s", $file));
    }

    /**
     * make a directory
     *
     * @param string $dir
     * @return string
     */
    public static function mkDir($dir)
    {
        $dir = escapeshellarg($dir);
        return self::exec(sprintf("mkdir -p %s", $dir));
    }

    /**
     * Execute a command
     *
     * @param string $cmd
     * @return string
     */
    public static function exec($cmd)
    {
        $error = 0;
        $return = '';
        exec($cmd . ' 2>&1', $return, $error);
        $return = implode("\n", $return);
//        if ($error) {
//            throw new RuntimeException($return);
//        }
        return $return;
    }

}