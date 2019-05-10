#! /usr/bin/php
<?php
/*
 * Tropotek Web Development Tools.
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2005 Michael Mifsud
 */
//include(dirname(dirname(__FILE__)) . '/vendor/autoload.php');
include dirname(__FILE__) . '/prepend.php';

$argv = $_SERVER['argv'];
$argc = $_SERVER['argc'];

if ($argc < 1 || $argc > 8) {
    echo $help;
    exit;
}

// define global vars
define('MAX_VER', 99999999999);
$cwd = rtrim(getcwd(), '/');
$version = '';
$dryRun = false;
$json  = false;
$forceTag = false;
$stable = true; // Force the minor version to be an even number EG: 0.0.0, 0.0.2
$verbose = 0;


$help = "
Usage: ".basename($argv[0])." [OPTIONS...]
Tag and release a repository project.
Currently only GIT and composer are supported.

If the code has a composer.json file with a `branch-alias`, that alias
number os prepended to the new minor number that will be created.

EG: `branch-alias`: { `dev-master`: `1.3.x-dev` }
The minor version number is found by searching the existing tags for the
next highest number. So if a version 1.3.34 was found to be the current
highest tag for the 1.3.x versions then this lib version would be 1.3.35.

If no `composer.json` file or no branch-alias exists then the svn repo
will be searched and the next highest minor version from the repository
will be created. NOTE: This gives you no control over the major version
unless supplied as a param with --version=x.x.x

Available options that this command can receive:

    --version                [N/A] Specify a new version number
    --dryrun                 If set, the final svn command is dumped to stdout
    --notstable              By default the script generates stable(even) version numbers IE: 1.0.2, 1.0.4, etc
                             set this options to enable single digit increments
    --json                   Show the composer.json to stdout on completion
    --forcetag               Forces a tag version even if there is no change from the previous version
    --verbose                [-v|vv|vvv] Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
    --quiet                  [-q] Turn off all messages, only errors will be displayed
    --help                   Show this help text

Copyright (c) 2002
Report bugs to info@tropotek.com
Tropotek home page: <http://www.tropotek.com/>
";

// Get var values from arguments
foreach ($argv as $k => $param) {
    if (strtolower(substr($param, 0, 10)) == '--version=') {
        $version = substr($param, 10);
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

    if (strtolower(substr($param, 0, 10)) == '--forcetag') {
        $forceTag = true;
    }
    if (strtolower(substr($param, 0, 6)) == '--json') {
        $json = true;
    }
    if (strtolower(substr($param, 0, 11)) == '--notstable') {
        $stable = false;
    }
    if (strtolower(substr($param, 0, 8)) == '--dryrun') {
        $dryRun = true;
    }
    if (strtolower(substr($param, 0, 6)) == '--help') {
        echo $help;
        exit;
    }
}
if ($json) $verbose = 0;

$vcs = null;
try {
    $exclude = array('composer.json', 'changelog.md');

    // Check if executed within a GIT repository
    $vcs = new \Tbx\Vcs\Adapter\Git($dryRun);
    $vcs->setVerbose($verbose);
    $currentBranch = $vcs->getCurrentBranch();

    //print_r($vcs);
    $vcs->log('------------------------------------------------------', \Tbx\Vcs\Adapter\Git::LOG_VV);
    $vcs->log('Repository: ' . $vcs->getUri(), \Tbx\Vcs\Adapter\Git::LOG_VV);
    //print_r('----------' . $vcs->getUri());



    // TODO: refactor this if we decide to use branching per tag....
    //$vcs->checkout('master');
    //$vcs->update();
    $vcs->commit('Finalising branch ' . $currentBranch . ' for tagged release.');

    // Check version needs tagging
    $tagList = $vcs->getTagList();
    $curVer = $vcs->getCurrentTag();

    if (!$curVer) {
        $curVer = '0.0.0';
    }
    $aliasVer = '';
    if (!$forceTag && count($tagList) && version_compare($curVer, '0.0.0', '>') && !$vcs->isDiff($curVer, $exclude)) {
        // bail if tag not needed or forced.
        $version = $curVer;
        $vcs->log('  Status: Skipped');
        $vcs->log('  Version: ' . $version);
        echo "\n";
        exit();
    }

    // Tag released changed
    $pkgTrunk = $pkg = \Tbx\Util::jsonEncode(file_get_contents('composer.json'));
    $vcs->log('Tagging: ' . $pkg->name);

    if ($pkg) {
        // Find branch-alias if one exists
        if (isset($pkg->extra->{'branch-alias'}->{'dev-trunk'})) {
            $aliasVer = $pkg->extra->{'branch-alias'}->{'dev-trunk'};
            $aliasVer = str_replace(array('.x-dev'), '.'.MAX_VER, $aliasVer);
        } else if (isset($pkg->extra->{'branch-alias'}->{'dev-master'})) {
            $aliasVer = $pkg->extra->{'branch-alias'}->{'dev-master'};
            $aliasVer = str_replace(array('.x-dev'), '.'.MAX_VER, $aliasVer);
        }
    }
    if (!$version || version_compare($version, $curVer, '<')) {
        $version = $vcs->incrementVersion($curVer, $aliasVer);
        if ($stable && ((int)substr($version, strrpos($version, '.')) % 2) != 0) {
            $version = $vcs->incrementVersion($version, $aliasVer);
        }
    }

    // Finally tag the release
    $vcs->commit('Creating tag: ' . $version);
    $vcs->tagRelease($version, 'Tag released for version: ' . $version);

    // Return the the branch we where at
    $vcs->checkout($currentBranch);

    $pkg->version = $version;
    $vcs->log('  Status: Released', \Tbx\Vcs\Adapter\Git::LOG_V);
    $vcs->log('  Version: ' . $version, \Tbx\Vcs\Adapter\Git::LOG_V);
    $vcs->log("  Changelog:\n\n" . $vcs->getChangelog(), \Tbx\Vcs\Adapter\Git::LOG_V);

    if ($json) {
        $vcs->log(\Tbx\Util::jsonEncode($pkg), \Tbx\Vcs\Adapter\Git::LOG_V);
    }

    echo "\n";
} catch (Exception $e) {
    echo ('ERROR: ' . $e->getMessage() . "\n");
    exit(-1);
}
exit();
