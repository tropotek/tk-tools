<?php
$classLoader = include(dirname(dirname(__FILE__)) . '/vendor/autoload.php');

$system = \Tk\System::instance();
$config = \Tk\Config::instance();
$factory = \Tk\Factory::instance();

try {

    // Define App Constants/Settings
    include_once(dirname(__DIR__) . '/src/config/config.php');

    \Tk\Factory::instance()->getBootstrap()->init();

} catch (\Exception $e) {
    error_log($e->__toString());
}
