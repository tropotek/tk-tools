#! /usr/bin/php
<?php
/*
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2005 Michael Mifsud
 */
include(dirname(dirname(__FILE__)) . '/vendor/autoload.php');


$argv = $_SERVER['argv'];
$argc = $_SERVER['argc'];
$help = "
Usage: ".basename($argv[0])." {options}
Short description of this command

Available options that this command can receive:

    --version=[version]         Test version option
    --debug                     Start command in debug mode
    --help                      Show this help text

Copyright (c) 2002
Report bugs to info@tropotek.com
Tropotek home page: <http://www.tropotek.com/>
";

$debug = false;

if ($argc < 1 || $argc > 2){
	echo $help;
	exit;
}
foreach ($argv as $param) {
    if (strtolower(substr($param, 0, 10)) == '--version=') {
        $version = substr($param, 10);
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
    // Script code here....


    echo $debug;

    exit();
} catch (Exception $e) {
    echo $help."\n";
    print('-'.$e->getMessage() . "\n");
    exit(-1);
}

