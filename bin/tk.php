#!/usr/bin/env php
<?php
include(dirname(__FILE__) . '/prepend.php');

//use Symfony\Component\Console\Application;
use Tbx\Console\Application;
use Tbx\Console\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

// Ensure only one instance running
//exec("ps aux | grep " . basename(__FILE__), $output, $return);
//if (count($output) > 3) exit();
set_time_limit(0);

try {
    $iniParams = array();

    $input = new ArgvInput();
    $output = new ConsoleOutput();

    $composer = json_decode(file_get_contents(dirname(__DIR__) . '/composer.json'));
    $title = 'Tropotek Command Utilities';
    $ver = $composer->version;
    $app = new Application($title, $ver);

    //Determine Environment
    $env = $input->getParameterOption(array('--env', '-e'), getenv('MYAPP_ENV') ?: 'prod');
    $app->environment = $env;

    // Git commands
    $app->add(new \Tbx\Console\Update());
    $app->add(new \Tbx\Console\Commit());
    $app->add(new \Tbx\Console\Status());
    $app->add(new \Tbx\Console\Tag());
    $app->add(new \Tbx\Console\TagShow());
    $app->add(new \Tbx\Console\TagProject());
    $app->add(new \Tbx\Console\DbBackup());

    // Other commands
    $app->add(new \Tbx\Console\Hash());
    $app->add(new \Tbx\Console\PassGen());

    $app->add(new \Tbx\Console\Test());

    $app->run($input, $output);
} catch (\Exception $e) {
  echo $e->__toString();
}

