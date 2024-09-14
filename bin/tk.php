#!/usr/bin/env php
<?php
include(dirname(__FILE__) . '/prepend.php');

use Bs\Factory;
use Tbx\Console\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Application;
use Tbx\Console\BranchShow;
use Tbx\Console\Commit;
use Tbx\Console\DbBackup;
use Tbx\Console\DbRestore;
use Tbx\Console\Hash;
use Tbx\Console\PassGen;
use Tbx\Console\Status;
use Tbx\Console\Tag;
use Tbx\Console\TagProject;
use Tbx\Console\TagShow;
use Tbx\Console\Test;
use Tbx\Console\Update;
use Tbx\Util;

set_time_limit(0);

try {
    $iniParams = [];
    $input = new ArgvInput();
    $output = new ConsoleOutput();

    $composer = Util::jsonDecode(file_get_contents(dirname(__DIR__) . '/composer.json'));

    $app = new Application('Tropotek Command Utilities', \Tk\System::getVersion());
    $app->setDispatcher(Factory::instance()->getEventDispatcher());

    //Determine Environment
    $env = $input->getParameterOption(array('--env', '-e'), \Tk\Config::instance()->get('env.type', 'prod'));

    // Git commands
    $app->add(new Update());
    $app->add(new Commit());
    $app->add(new Status());
    $app->add(new Tag());
    $app->add(new TagShow());
    $app->add(new BranchShow());
    $app->add(new TagProject());
    $app->add(new DbBackup());
    $app->add(new DbRestore());

    // Other commands
    $app->add(new Hash());
    $app->add(new PassGen());
    $app->add(new Test());

    $app->run($input, $output);
} catch (\Exception $e) {
  echo $e->__toString();
}

