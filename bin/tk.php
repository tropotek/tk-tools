#!/usr/bin/env php
<?php
include(dirname(__FILE__) . '/prepend.php');

use Bs\Factory;
use Tbx\Console\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Application;
use Tbx\Console\BackupClean;
use Tbx\Console\BranchShow;
use Tbx\Console\Commit;
use Tbx\Console\DbBackup;
use Tbx\Console\DbRestore;
use Tbx\Console\Enc;
use Tbx\Console\Hash;
use Tbx\Console\PassGen;
use Tbx\Console\SiteBackup;
use Tbx\Console\Status;
use Tbx\Console\Tag;
use Tbx\Console\TagLibs;
use Tbx\Console\TagShow;
use Tbx\Console\Test;
use Tbx\Console\Update;

set_time_limit(0);

try {
    $input = new ArgvInput();
    $output = new ConsoleOutput();

    $app = new Application('Tropotek Command Utilities', \Tk\System::getVersion());

    //Determine Environment
    $env = $input->getParameterOption(array('--env', '-e'), \Tk\Config::instance()->get('env.type', 'prod'));

    // Git commands
    $app->add(new Update());
    $app->add(new Commit());
    $app->add(new Status());
    $app->add(new Tag());
    $app->add(new TagShow());
    $app->add(new BranchShow());
    $app->add(new TagLibs());
    $app->add(new DbBackup());
    $app->add(new DbRestore());
    $app->add(new Enc());
    $app->add(new SiteBackup());
    $app->add(new BackupClean());

    // Other commands
    $app->add(new Hash());
    $app->add(new PassGen());
    $app->add(new Test());

    $app->run($input, $output);
} catch (\Exception $e) {
  echo $e->__toString();
}

