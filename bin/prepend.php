<?php
$classLoader = include(dirname(dirname(__FILE__)) . '/vendor/autoload.php');

$config = \Tk\Config::getInstance();
try {
    if (is_writable($config->getLogPath())) {
        $logger = new \Monolog\Logger('system');
        $handler = new \Monolog\Handler\StreamHandler($config->getLogPath(), $config->getLogLevel());
        $formatter = new \Tk\Log\MonologLineFormatter();
        $formatter->setScriptTime($config->getScriptTime());
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);
        $config->setLog($logger);
        \Tk\Log::getInstance($logger);
    } else {
        error_log('Log Path not readable: ' . $config->getLogPath());
    }
} catch (\Exception $e) {
    error_log('Log Path not readable: ' . $config->getLogPath());
}
