#! /usr/bin/php
<?php
/*
 * Tropotek Web Development Tools.
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2005 Michael Mifsud
 */
include(dirname(dirname(__FILE__)) . '/vendor/autoload.php');

$argv = $_SERVER['argv'];
$argc = $_SERVER['argc'];
$commitMsg = isset($argv[1]) ? $argv[1] : '';

if ($argc < 1 || $argc > 8) {
    echo $help;
    exit;
}

// define global vars
define('MAX_VER', 99999999999);
$cwd = getcwd();
$repo = rtrim(@$argv[1], '/');
$version = '';
$dryRun = false;
$noClean = false;
$json  = false;
$forceTag = false;
$stable = true; // Force the minor version to be an even number EG: 0.0.0, 0.0.2
$tmp = sys_get_temp_dir();
if (!$tmp) {
    $tmp = '/tmp';
}
$tmp .= '/tk_'.md5(time());


$help = "
Usage: ".basename($argv[0])." [SVN-URL] [OPTIONS...]
Tag a single package release from the repository. Works only on trunk packages.
This command will search the repository for the next version number.

If the code has a composer.json file with a `branch-alias`, that alias
number os prepended to the new minor number that will be created.

EG: `branch-alias`: { `dev-trunk`: `1.3.x-dev` }
The minor version number is found by searching the existing tags for the
next highest number. So if a version 1.3.34 was found to be the current
highest tag for the 1.3.x versions then this lib version would be 1.3.35.

If no `composer.json` file or no branch-alias exists then the svn repo
will be searched and the next highest minor version from the repository
will be created. NOTE: This gives you no control over the major version
unless supplied as a param with --version=x.x.x

Available options that this command can receive:

    --notstable              By default the script generates stable(even) version numbers IE: 1.0.2, 1.0.4, etc
                             set this options to enable single digit increments
    --json                   Show the composer.json to stdout on completion
    --force                  Forces a tag version even if there is no change from the previous version
    --version                Specify a new version number
    --dryrun                 If set, the final svn command is dumped to stdout
    --noclean                If set, does not delete the contents of the checked out files
    --tmpPath                If set, change the tmp path, Default `$tmp`
    --help                   Show this help text

Copyright (c) 2002-2020
Report bugs to info@tropotek.com
Tropotek home page: <http://www.tropotek.com/>
";

// Get var values from arguments
foreach ($argv as $k => $param) {
    if (strtolower(substr($param, 0, 10)) == '--tmppath=') {
        $tmp = strtolower(substr($param, 10));
    }
    if (strtolower(substr($param, 0, 10)) == '--version=') {
        $version = strtolower(substr($param, 10));
    }
    if (strtolower(substr($param, 0, 10)) == '--forcetag') {
        $forceTag = true;
    }
    if (strtolower(substr($param, 0, 7)) == '--force') {
        $forceTag = true;
    }
    if (strtolower(substr($param, 0, 6)) == '--json') {
        $json = true;
    }
    if (strtolower(substr($param, 0, 11)) == '--notstable') {
        $stable = false;
    }
    if (strtolower(substr($param, 0, 9)) == '--noclean') {
        $noClean = true;
    }
    if (strtolower(substr($param, 0, 8)) == '--dryrun') {
        $dryRun = true;
    }
    if (strtolower(substr($param, 0, 6)) == '--help') {
        echo $help;
        exit;
    }
}

try {


    if (!preg_match('/^[a-z0-9]{2,8}:\/\/(www\.)?[\S]+$/i', $repo)) {
        throw new Exception("ERROR: Please supply a valid repository URI: " . $repo);
    }
    if ($version && !preg_match('/^([0-9]{1,4}\.?){2,4}$/', $version)) {
        throw new Exception("ERROR: Invalid version: " . $version);
    }

    if (!is_dir($tmp)) {
        mkdir($tmp, 0777, true);
    }
    echo ' ------------------------------------------------------' . "\n";
    echo ' - Repository: ' . $repo . "\n";

    if (preg_match('/git/', $repo)) {
        $vcs = new \Tbx\Vcs\Adapter\Git($repo, $tmp, $dryRun, $noClean);
    } else {
        $vcs = new \Tbx\Vcs\Adapter\Svn($repo, $tmp, $dryRun, $noClean);
    }
    $tagList = $vcs->getTagList();

    $curVer = $vcs->getCurrentTag();
    if (!$curVer) {
        $curVer = '0.0.0';
    }
    $newVer = '';
    $aliasVer = '';

    if (!$forceTag && count($tagList) && version_compare($curVer, '0.0.0', '>') && !$vcs->isDiff($curVer)) {
        $version = $curVer;
        echo " - Status: Skipped\n";
        echo " - Version: $version\n";
    } else {

        // Only checkout if versions need updating
        echo ' - Checking Out '."\n";
        $vcs->checkout();
        sleep(1);

        // get trunk composer.json file
        $pkgTrunk = $pkg = json_decode($vcs->getFileContents('/composer.json'));
        echo ' - Tagging: ' . $pkg->name . "\n";

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
        $vcs->commit('tag: Creating tag: ' . $version);
        sleep(1);
        $vcs->tagRelease($version, 'Tag released for version: ' . $version);
        sleep(1);
        $pkg->version = $version;
        echo " - Status: Released\n";
        echo " - Version: $version\n";

        echo "\nCHANGELOG:\n";
        echo $vcs->getChangelog();

        if ($json) {
            echo jsonPrettyPrint(json_encode($pkg)) . "\n";
        }
    }

    echo ' - ' . "\n";

} catch (Exception $e) {
    print('-'.$e->getMessage(). "\n");
    if (!$noClean) {
        @exec('rm -rf ' . escapeshellarg($tmp));
    }
    exit(-1);
}
if (!$noClean) {
    @exec('rm -rf ' . escapeshellarg($tmp));
}
exit();
