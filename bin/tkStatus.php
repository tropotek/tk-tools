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



    if (is_dir($cwd . '/.svn')) {   // if current path has .svn commit this repos
        //echo " - Updating Repository: " . $cwd . "\n";
        $p = escapeshellarg($cwd);
        echo `cd $p && svn update`;
    } else {    // Commit any subdirecories that contain .svn. (warning recursive)
        foreach (new DirectoryIterator($cwd) as $res) {
            if ($res->isDot() || substr($res->getFilename(), 0, 1) == '_') {
                continue;
            }
            $path = $res->getRealPath();
            if ($res->isDir() && is_dir($path . '/.svn')) {
                //echo " - Update child working directory: " . $path . "\n";
                $p = escapeshellarg($path);
                $cmd = basename($argv[0]);
                echo `cd $p && $cmd`;
                //echo `svn update $p`;
            }
        }
    }

    foreach ($vendorPaths as $vendorPath) {
        $vendorPath = $cwd . $vendorPath;
        if (is_dir($vendorPath)) {      // If vendor path exists
            foreach (new DirectoryIterator($vendorPath) as $res) {
                if ($res->isDot() || substr($res->getFilename(), 0, 1) == '_') {
                    continue;
                }
                $path = $res->getRealPath();
                echo $path."\n";
                if ($res->isDir() && is_dir($path . '/.svn')) {
                    //echo " - Update Repository: " . basename($path) . "\n";
                    $p = escapeshellarg($path);
                    $cmd = basename($argv[0]);
                    echo `svn status $p`;
                }
            }
        }
    }

   echo "\n";
} catch(Exception $e) {
    print(basename($argv[0]) . ": \n" . $e->__toString());
    exit(-1);
}
