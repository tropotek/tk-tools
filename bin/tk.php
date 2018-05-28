#!/usr/bin/env php
<?php
include(dirname(__FILE__) . '/prepend.php');

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;


// Ensure only one instance running
//exec("ps aux | grep " . basename(__FILE__), $output, $return);
//if (count($output) > 3) exit();
set_time_limit(0);

$input = new ArgvInput();
$output = new ConsoleOutput();

//Determine Environment
$env = $input->getParameterOption(array('--env', '-e'), getenv('MYAPP_ENV') ?: 'prod');
$app['environment'] = $env;


$composer = json_decode(file_get_contents(dirname(__DIR__) . '/composer.json'));

$title = 'Tropotek Command Utilities';
$ver = $composer->version;
$app = new Application($title, $ver);

$app->add(new \Tbx\Console\Test());


try {
    $app->run($input, $output);
} catch (\Exception $e) {
  echo $e->__toString();
}

