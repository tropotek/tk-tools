<?php

use Tk\Config;
use Tk\Debug\VarDump;
use Tk\ErrorHandler;
use Tk\Log;
use Tk\Logger\ErrorLog;
use Tk\Logger\StreamLog;

$classLoader = include(dirname(dirname(__FILE__)) . '/vendor/autoload.php');

try {
    $config = Config::instance();

    $logLevel = Config::getValue('log.logLevel', \Psr\Log\LogLevel::DEBUG);
    $logfile = Config::getValue('php.error_log', ini_get('error_log'));
    if (is_writable($logfile)) {
        Log::addLogger(new StreamLog($logfile, $logLevel));
    } else {
        Log::addLogger(new ErrorLog($logLevel));
    }
    ErrorHandler::instance();
    VarDump::instance();


} catch (\Exception $e) {
    error_log($e->__toString());
}
