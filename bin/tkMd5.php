#!/usr/bin/php
<?php

$argv = $_SERVER['argv'];
$argc = $_SERVER['argc'];

echo md5($argv[1]);
echo "\n";
?>

