#! /usr/bin/php
<?php
/*
 * Tropotek Web Development Tools.
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2005 Michael Mifsud
 */

$argv = $_SERVER['argv'];
$argc = $_SERVER['argc'];
$help = "
Backdoor Password Generator for Tk sites...
   Usage: ".basename($argv[0])."

NOTE: Some sites cannot be accessed with a generated password.

";

$key = date('=d-m-Y=', time());


if ($argc < 1 || $argc > 1){
	echo $help;
	exit;
}
foreach ($argv as $param) {
    if (strtolower(substr($param, 0, 6)) == '--help') {
        echo $help;
        exit;
    }
}


try {
    $password = md5($key);
    echo $password . "\n";
} catch(Exception $e) {
    print(basename($argv[0]) . ": \n" . $e->__toString());
    exit(-1);
}
