#! /usr/bin/php
<?php
/*
 * Tropotek Web Development Tools.
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2005 Michael Mifsud
 */
include dirname(__FILE__) . '/prepend.php';

$argv = $_SERVER['argv'];
$argc = $_SERVER['argc'];
$commitMsg = isset($argv[1]) ? $argv[1] : '';
$help = "
Usage: ".basename($argv[0])." [Message] {options}
This command is used to update projects and libraries from a remote repository.
It will iterate through all nested Tk libs and update any changes.

Available options that this command can receive:

    --noVendor               Disable recurring into vendor folders.
    --debug                  Start command in debug mode
    --dryrun                 If set, the final svn command is dumped to stdout
    --verbose                [-v|vv|vvv] Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
    --quiet                  [-q] Turn off all messages, only errors will be displayed
    --help                   Show this help text.

Copyright (c) 2002
Report bugs to info@tropotek.com
Tropotek home page: <http://www.tropotek.com/>
";

if ($argc < 1 || $argc > 1){
  echo $help;
  exit;
}

$cwd = getcwd();
$dryRun = false;
$verbose = 0;

foreach ($argv as $param) {

    if (strtolower(substr($param, 0, 8)) == '--dryrun') {
        $dryRun = true;
    }
    if (strtolower(substr($param, 0, 7)) == '--quiet') {
        $verbose = 0;
    }
    if (strtolower(substr($param, 0, 2)) == '-q') {
        $verbose = 0;
    }
    if (strtolower(substr($param, 0, 9)) == '--verbose') {
        $verbose = 5;
    }
    if (strtolower(substr($param, 0, 2)) == '-v') {
        $verbose = 1;
    }
    if (strtolower(substr($param, 0, 3)) == '-vv') {
        $verbose = 3;
    }
    if (strtolower(substr($param, 0, 4)) == '-vvv') {
        $verbose = 5;
    }
    if (strtolower(substr($param, 0, 6)) == '--help') {
        echo $help;
        exit;
    }
}

try {
    // Check if executed within a GIT repository
    $vcs = new \Tbx\Vcs\Adapter\Git($dryRun);
    $vcs->setVerbose($verbose);

    if (!is_dir($cwd . '/.git')) {   // GIT
        $vcs->log('This folder does not appear to be a GIT repository.', \Tbx\Vcs\Adapter\Git::LOG);
        exit;
    }

    $vcs->log('------------------------------------------------', \Tbx\Vcs\Adapter\Git::LOG);
    $vcs->log('UPDATE: ' . $cwd, \Tbx\Vcs\Adapter\Git::LOG);
    $vcs->update();

    // Update for project folders
    foreach ($vendorPaths as $vPath) {
        $vendorPath = rtrim($cwd, '/') . $vPath;
        if (is_dir($vendorPath)) {      // If vendor path exists
            foreach (new DirectoryIterator($vendorPath) as $res) {
                if ($res->isDot() || substr($res->getFilename(), 0, 1) == '_') {
                    continue;
                }
                $path = $res->getRealPath();
                if (!$res->isDir() && !is_dir($path.'/.git')) {
                    continue;
                }
                $cmd = sprintf('cd %s && %s', escapeshellarg($path), basename($argv[0]));
                $vcs->log($cmd, \Tbx\Vcs\Adapter\Git::LOG_VVV);
                system($cmd, $out);
                $vcs->log($out, \Tbx\Vcs\Adapter\Git::LOG_DEBUG);
            }
        }
    }
} catch(Exception $e) {
    print(basename($argv[0]) . ": \n" . $e->__toString());
    exit(-1);
}
