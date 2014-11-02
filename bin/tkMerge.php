#! /usr/bin/php
<?php
/*
 * Tropotek Web Development Tools.
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2005 Michael Mifsud
 */
include dirname(__FILE__).'/prepend.php';

$argv = $_SERVER['argv'];
$argc = $_SERVER['argc'];
$commitMsg = isset($argv[1]) ? $argv[1] : '';
$help = "
Usage: ".basename($argv[0])." {options}
This command is used to merge TK lib projects and libraries from the trunk branch to the current
working directory. It will iterate through all nested checked out repositories and merge them to.

Available options that this command can receive:

    --noVendor              Disable recursing into vendor folders.
    --debug                  Start command in debug mode
    --help                  Show this help text

Copyright (c) 2002-2020
Report bugs to info@tropotek.com
Tropotek home page: <http://www.tropotek.com/>
";

if ($argc < 1 || $argc > 2) {
    echo $help;
    exit;
}
$cwd = getcwd();
$project = basename(dirname($cwd));
$novendor = false;
$debug = false;



throw new Exception('Cannot use without destroying composer files. Look into it;');
exit;



foreach ($argv as $param) {
    if (strtolower(substr($param, 0, 10)) == '--novendor') {
        $novendor = true;
    }
    if (strtolower(substr($param, 0, 7)) == '--debug') {
        $debug = true;
    }
    if (strtolower(substr($param, 0, 6)) == '--help') {
        echo $help;
        exit;
    }
}

try {


    if (is_dir($cwd . '/.svn')) {   // if current path has .svn commit this repos
        echo " - merge working directory: " . $cwd . "\n";
        $info = simplexml_load_string(`svn info --xml `);
        if (preg_match('/\/trunk$/', $info->entry->url)) {
            throw new Exception('Cannot merge trunk to itself.');
        }
        $trunkUrl = dirname(dirname($info->entry->url)). '/trunk';
        $cmd = sprintf('svn merge %s ./ ', $trunkUrl);
        _exec($cmd);
    } else {    // merge any subdirectories that contain .svn. (warning recursive)
        foreach (new DirectoryIterator($cwd) as $res) {
            if ($res->isDot() || substr($res->getFilename(), 0, 1) == '_') {
                continue;
            }
            $path = $res->getRealPath();
            if ($res->isDir() && is_dir($path . '/.svn')) {
                $p = escapeshellarg($path);
                $cmd = basename($argv[0]) . ' --novendor';
                _exec("cd $p && $cmd");
            }
        }
    }

    // New composer style commit
    if(!$novendor) {
        foreach ($vendorPaths as $vendorPath) {
            $vendorPath = $cwd . $vendorPath;
            if (is_dir($vendorPath)) {
                foreach (new DirectoryIterator($vendorPath) as $res) {
                    if ($res->isDot() || substr($res->getFilename(), 0, 1) == '_') {
                        continue;
                    }
                    $path = $res->getRealPath();
                    if ($res->isDir() && is_dir($path . '/.svn')) {
                        $p = escapeshellarg($path);
                        $cmd = basename($argv[0]) . ' --novendor';
                        _exec("cd $p && $cmd");
                    }
                }
            }
        }
    }

} catch (Exception $e) {
    print(basename("\nERROR: ".$e->getMessage().' [' . $e->getLine() ."]\n"));
    echo $help;
    exit(-1);
}

/**
 *
 * @param $cmd
 * @return string
 */
function _exec($cmd)
{
    global $debug;
    if ($debug) {
        echo $cmd . "\n";
        return;
    }
    echo `$cmd`;
}