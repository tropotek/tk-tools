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
This command is used to shaw the latest tag for the project and any
  in-house vendor libs that it is dependent on.

Available options that this command can receive:

    --noVendor              Disable recurring into vendor folders.
    --debug                 Start command in debug mode
    --help                  Show this help text.

Copyright (c) 2017
Report bugs to info@tropotek.com
Tropotek home page: <http://www.tropotek.com/>
";

if ($argc < 1 || $argc > 4) {
    echo $help;
    exit;
}
$cwd = getcwd();
$externFile = $cwd . '/externals';
$project = basename($cwd);
$projectPath = $cwd;
$novendor = false;


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
    $p = escapeshellarg($cwd);

    $vpath = '';
    if (!$novendor) {
        foreach ($vendorPaths as $vp1) {
          if (preg_match('|'.preg_quote($vp1).'|', $p, $regs)) {
            $vpath = $vp1;
            break;
          }
        }
    }

    if (is_dir($cwd . '/.git')) {   // GIT
        $latestTag = exec('git describe --abbrev=0 --tags --always', $output, $return);
        if ($return === 0 && $latestTag) {
            echo sprintf('%-32s: %s',$vpath.'/'.$project, $latestTag);
        }
        echo "\n";
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

                    $cmd = sprintf('cd %s && %s', escapeshellarg($path), basename($argv[0]));
                    system($cmd);
                }
            }
        }
    }
} catch (Exception $e) {
    print(basename("\nERROR: ".$e->getMessage().' [' . $e->getLine() ."]\n"));
    echo $help;
    exit(-1);
}

