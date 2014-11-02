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
Usage: ".basename($argv[0])." [Message] {options}
This command is used to commit projects and libraries to the SVN.
It will iterate through all nested checked out repositories and commit them to.

Available options that this command can receive:

    --noVendor              Disable recurring into vendor folders.
    --debug                 Start command in debug mode
    --help                  Show this help text.

Copyright (c) 2002-2020
Report bugs to info@tropotek.com
Tropotek home page: <http://www.tropotek.com/>
";

if ($argc < 1 || $argc > 4) {
    echo $help;
    exit;
}
$cwd = getcwd();
$externFile = $cwd . '/externals';

$project = basename(dirname($cwd));
$commitMsg = @$argv[1];
$novendor = false;


//define('DEBUG', true);

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

    if (!$commitMsg) {
        throw new Exception('Please add a commit message.');
        //$commitMsg = 'Auto commit from ' . $project;
    }
    if (is_dir($cwd . '/.svn')) {   // if current path has .svn commit this repos
        echo " - Commit: " . $cwd . "\n";
        _exec("cd '$cwd' && svn ci -m '$commitMsg'");
    } else {    // Commit any subdirectories that contain .svn. (warning recursive)
        foreach (new DirectoryIterator($cwd) as $res) {
            if ($res->isDot() || substr($res->getFilename(), 0, 1) == '_') {
                continue;
            }
            $path = $res->getRealPath();
            if ($res->isDir() && is_dir($path . '/.svn')) {
                $p = escapeshellarg($path);
                $cmd = basename($argv[0]) . ' \'' . $commitMsg . '\' --novendor';
                _exec("cd $p && $cmd");
            }
        }
    }

    // Compat: If project has externals (old tkLib commit style)
    if (is_file($externFile)) {
        $file = explode("\n", trim(file_get_contents($externFile)));
        foreach ($file as $line) {
            if (!$line || $line[0] == '#') continue;
            preg_match('/(\S+)\s+(\S+)/', $line, $regs);
            if (isset($regs[1])) {
                echo " - Commit package: " . $regs[1] . "\n";
                _exec("svn ci -m '$commitMsg' lib/{$regs[1]}");
            }
        }
    }

    // New composer style commit
    if (!$novendor) {
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
                        $cmd = basename($argv[0]) . ' \'' . $commitMsg . '\' --novendor ';
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