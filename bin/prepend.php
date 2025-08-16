<?php

use Tk\Config;

$classLoader = include(dirname(dirname(__FILE__)) . '/vendor/autoload.php');

try {
    $config = Config::instance();
} catch (\Exception $e) {
    error_log($e->__toString());
}
