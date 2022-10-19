<?php
$classLoader = include(dirname(dirname(__FILE__)) . '/vendor/autoload.php');

$system = \Tk\System::instance();
$config = \Tk\Config::instance();
$factory = \Tk\Factory::instance();

try {

    // Init the tk vardump functions
    \Tk\Debug\VarDump::instance($factory->getLogger(), dirname(dirname(__FILE__)));

    // Init framework error handler
    \Tk\ErrorHandler::instance($factory->getLogger());

} catch (\Exception $e) {
    error_log($e->__toString());
}
