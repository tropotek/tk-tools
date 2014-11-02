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

if ($argc < 1 || $argc > 6) {
    echo $help;
    exit;
}

// define global vars
define('MAX_VER', 99999999999);
$cwd = getcwd();
$repo = rtrim(@$argv[1], '/');
$dryRun = false;
$showJson = false;
$staticVer = false;
$forceTag = false;
$tmp = sys_get_temp_dir();
if (!$tmp) {
    $tmp = '/tmp';
}
$tmp .= '/tk_'.md5(time());
$packagePrefixList = array('ttek', 'ttek\-plugin', 'ttek\-theme', 'tropotek', 'tropotek\-plugin', 'tropotek\-theme', 'ems\-plugin', 'ems');


$help = "
Usage: " . basename($argv[0]) . " [SVN-URL] [OPTIONS...]
Tag a release from the repository. Works only on trunk projects.
This command will search the search the project for all packages
in use and tag and release them with new version along with the
parent project.

Available options that this command can receive:

    --static                 If set, then the existing composer.json \'require\'  versions are
                             updated to use specific versions of the libs
                             EG: ~1.0 becomes 1.0.6 for example.
    --force                  Forces a tag version even if there is no change from the previous version
    --showJson               Output package release info as a json object.
    --remotePkgUri           The remote server that contains the compiled package json files.
    --dryRun                 If set the final svn command is dumped to stdout
    --tmpPath                If set change the tmp path, Default `$tmp`
    --help                   Show this help text

Copyright (c) 2002-2020
Report bugs to info@tropotek.com
Tropotek home page: <http://www.tropotek.com/>
";

// Get var values from arguments
foreach ($argv as $param) {
    if (strtolower(substr($param, 0, 10)) == '--showjson') {
        $showJson = true;
    }
    if (strtolower(substr($param, 0, 10)) == '--tmppath=') {
        $tmp = strtolower(substr($param, 10));
    }
    if (strtolower(substr($param, 0, 10)) == '--forcetag') {
        $forceTag = true;
    }
    if (strtolower(substr($param, 0, 7)) == '--force') {
        $forceTag = true;
    }
    if (strtolower(substr($param, 0, 8)) == '--static') {
        $staticVer = true;
    }
    if (strtolower(substr($param, 0, 8)) == '--dryrun') {
        $dryRun = true;
    }
    if (strtolower(substr($param, 0, 6)) == '--help') {
        echo $help;
        exit;
    }
}

$dr = '';
if ($dryRun) {
    $dr = '--dryRun';
}
$forceTagStr = '';
if ($forceTag) {
    $forceTagStr = '--force';
}

try {

    if (!preg_match('/^[a-z0-9]{2,8}:\/\/(www\.)?[\S]+$/i', $repo)) {
        throw new Exception("ERROR: Please supply a valid repository URI: " . $repo);
    }

    echo "TMP PATH: $tmp \n";
    if (!is_dir($tmp)) {
        mkdir($tmp, 0777, true);
    }

    if (preg_match('/git/', $repo)) {
        $vcs = new \Tbx\Vcs\Adapter\Git($repo, $tmp, $dryRun, true);
    } else {
        $vcs = new \Tbx\Vcs\Adapter\Svn($repo, $tmp, $dryRun, true);
    }

    $vcs->checkout();
    if (!$vcs->isFile('/composer.json')) {
        throw new Exception('Only composer projects can be released.');
    }

    // get trunk composer.json file
    $tagJson = $trunkJson = json_decode($vcs->getFileContents('/composer.json'));

    if (!$trunkJson) {
        throw new Exception('Error reading composer.json file in project root.');
    }
    if ($trunkJson->type != 'project') {
        throw new Exception('Only package types can be released.');
    }

    $packages = array();
    foreach (get_object_vars($trunkJson->require) as $name => $ver) {
        $regex = implode('|', $packagePrefixList);
        if (!preg_match('/^('.$regex.')\//i', $name)) continue;
        $json = '';
        foreach($trunkJson->repositories as $repoObj) {
            $json = fileGetContents($repoObj->url . 'p/' . $name . '.json');
            $json = json_decode($json);
            if ($json !== null) break;
        }
        if (!$json) continue;

        $packages[$name] = array(
            'name' => $name
        );
        if (isset($json->packages->{$name}->{'dev-trunk'})) {
            $packages[$name]['uri'] = $json->packages->{$name}->{'dev-trunk'}->source->url;
            $packages[$name]['branch-alias'] = $json->packages->{$name}->{'dev-trunk'}->extra->{'branch-alias'}->{'dev-trunk'};
        } else if (isset($json->packages->{$name}->{'dev-master'})) {
            $packages[$name]['uri'] = $json->packages->{$name}->{'dev-master'}->source->url;
            $packages[$name]['branch-alias'] = $json->packages->{$name}->{'dev-master'}->extra->{'branch-alias'}->{'dev-master'};
        }

        $verList = array_keys(get_object_vars($json->packages->{$name}));
        sortVersionArray($verList);
        $currVer = array_pop($verList);
        $packages[$name]['version'] = $currVer;

        $cmd = sprintf('tkTag %s %s %s --noclean --tmpPath=%s', escapeshellarg($packages[$name]['uri']), $dr, $forceTagStr, escapeshellarg($tmp));

        // Use exec
        $line = system($cmd);
        if ($line === false) {
            throw new Exception('Error tagging package: ' . $name);
        }
        $newVersion = trim(substr($line, 1));
        $packages[$name]['newVersion'] = $newVersion;
        $packages[$name]['released'] = false;
        if ($packages[$name]['newVersion'] != $packages[$name]['version']) {
            $packages[$name]['released'] = true;
        }
        if ($staticVer) {
            $tagJson->require->{$name} = $packages[$name]['newVersion'];
        }

    }
    // Update composer file
    $tagJson->{'minimum-stability'} = 'stable';
    $vcs->setFileContents('/composer.json', jsonPrettyPrint(json_encode($tagJson)));
    $vcs->commit();


    $cmd = sprintf('tkTag %s %s %s --noclean --tmpPath=%s', escapeshellarg($repo), $dr, $forceTagStr, escapeshellarg($tmp));

    // Use exec
    $line = system($cmd);
    $line = trim($line);
    $newVersion = trim(substr($line, 1));
    $released = trim(substr($line, 0, 1)) == '+' ? true : false;


    // Add Root site to packages array for output
    $name = $trunkJson->name;
    $packages[$name]['name'] = $name;
    $packages[$name]['uri'] = $repo;
    if (isset($trunkJson->extra->{'ranch-alias'}->{'dev-trunk'})) {
        $packages[$name]['branch-alias'] = $trunkJson->extra->{'ranch-alias'}->{'dev-trunk'};
    } else if (isset($trunkJson->extra->{'ranch-alias'}->{'dev-master'})) {
        $packages[$name]['branch-alias'] = $trunkJson->extra->{'ranch-alias'}->{'dev-master'};
    }
    $packages[$name]['newVersion'] = $newVersion;
    $packages[$name]['released'] = $released;

    // Reset the trunk for dev mode
    $trunkJson->{'minimum-stability'} = 'dev';
    $vcs->setFileContents('/composer.json', jsonPrettyPrint(json_encode($trunkJson)));
    $vcs->commit();

    echo "\n";
} catch (Exception $e) {
    echo $help."\n";
    print('-'.$e->getMessage() . "\n");
    exec('rm -rf ' . escapeshellarg($tmp));
    exit(-1);
}


exec('rm -rf ' . escapeshellarg($tmp));
exit();




function fileGetContents($url)
{
    //vd($_SERVER, parse_url($url));

    $no_proxy = array();
    if (isset($_SERVER['no_proxy'])) {
      $no_proxy = explode(',', $_SERVER['no_proxy']);
    }
    $parseUrl = parse_url($url);

    $ch = curl_init($url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    if (isset($_SERVER['http_proxy']) && !in_array($parseUrl['host'], $no_proxy)) {
        curl_setopt($ch, CURLOPT_PROXY, $_SERVER['http_proxy']);
    }
    $output=curl_exec($ch);
    curl_close($ch);
    return $output;
}

function sortVersionArray(&$array)
{
    return usort($array, function ($a, $b) {
        if ($a == $b) {
            return 0;
        } else if (version_compare($a, $b, '<')) {
            return -1;
        } else {
            return 1;
        }
    });
}
