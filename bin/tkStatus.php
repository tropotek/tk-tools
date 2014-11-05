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
$help = "Usage: " . basename($argv[0]) . "\n\n";

if ($argc < 1 || $argc > 1){
  echo $help;
  exit;
}

$cwd = getcwd();

foreach ($argv as $param) {
    if (strtolower(substr($param, 0, 6)) == '--help') {
        echo $help;
        exit;
    }
}

try {

    $p = escapeshellarg($cwd);

    if (is_dir($cwd . '/.git')) {   // GIT
        echo "STATUS: " . $p . "\n";
        echo '  - GIT: ' . `cd $p && git status`;
    } else if (is_dir($cwd . '/.svn')) {   // SVN
        echo "STATUS: " . $p . "\n";
        echo '  - SVN: ' . `cd $p && svn status`;
    }

    // Update for project folders
    foreach ($vendorPaths as $vPath) {
        $vendorPath = rtrim($cwd, '/') . $vPath;
        if (is_dir($vendorPath)) {      // If vendor path exists
            foreach (new DirectoryIterator($vendorPath) as $res) {
                if ($res->isDot() || substr($res->getFilename(), 0, 1) == '_') {
                    continue;
                }
                $path = $res->getRealPath();
                if (!$res->isDir() && !is_dir($path.'/.svn') && !is_dir($path.'/.git')) {
                    continue;
                }
                $p = escapeshellarg($path);
                $cmd = basename($argv[0]);
                echo `cd $p && $cmd`;
            }
        }
    }
} catch(Exception $e) {
    print(basename($argv[0]) . ": \n" . $e->__toString());
    exit(-1);
}
