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

    $p = escapeshellarg($cwd);
    $commitMsg = escapeshellarg($commitMsg);

    if (is_dir($cwd . '/.git')) {   // GIT
        echo "COMMIT: " . $p . "\n";
        echo '  - GIT: ' . `cd $p && git commit -am $commitMsg && git push`;
    } else if (is_dir($cwd . '/.svn')) {   // SVN
        echo "COMMIT: " . $p . "\n";
        echo '  - SVN: ' . `cd $p && svn ci -m $commitMsg`;
    }

    // Commit child projects
    if (!$novendor) {
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
                    echo `cd $p && $cmd  $commitMsg --novendor `;
                }
            }
        }
    }

} catch (Exception $e) {
    print(basename("\nERROR: ".$e->getMessage().' [' . $e->getLine() ."]\n"));
    echo $help;
    exit(-1);
}

