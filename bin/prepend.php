<?php

use Bs\Factory;
use Tk\Config;

$classLoader = include(dirname(dirname(__FILE__)) . '/vendor/autoload.php');

try {
    $config = Config::instance();
    $factory = Factory::instance();

    Factory::instance()->getBootstrap()->init();

} catch (\Exception $e) {
    error_log($e->__toString());
}
