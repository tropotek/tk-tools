#!/usr/bin/env php
<?php
include(dirname(__FILE__) . '/prepend.php');

use Tbx\Console\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Application;

set_time_limit(0);

try {
    $iniParams = [];
    $input = new ArgvInput();
    $output = new ConsoleOutput();

    $system = \Tk\System::instance();
    $config = \Tk\Config::instance();
    $factory = \Tk\Factory::instance();

    $composer = \Tbx\Util::jsonDecode(file_get_contents(dirname(__DIR__) . '/composer.json'));

    $app = new Application('Tropotek Command Utilities', $system->getVersion());
    $app->setDispatcher($factory->getEventDispatcher());

    //Determine Environment
    $env = $input->getParameterOption(array('--env', '-e'), getenv('MYAPP_ENV') ?: 'prod');
    $app->environment = $env;

    // Git commands
    $app->add(new \Tbx\Console\Update());
    $app->add(new \Tbx\Console\Commit());
    $app->add(new \Tbx\Console\Status());
    $app->add(new \Tbx\Console\Tag());
    $app->add(new \Tbx\Console\TagShow());
    $app->add(new \Tbx\Console\BranchShow());
    $app->add(new \Tbx\Console\TagProject());
    $app->add(new \Tbx\Console\DbBackup());
    $app->add(new \Tbx\Console\DbRestore());

    // Other commands
    $app->add(new \Tbx\Console\Hash());
    $app->add(new \Tbx\Console\PassGen());
    //$app->add(new \Tbx\Console\Test());

    $app->run($input, $output);
} catch (\Exception $e) {
  echo $e->__toString();
}

