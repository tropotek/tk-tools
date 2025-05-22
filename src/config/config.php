<?php
/**
 * application configuration parameters
 */
use Tk\Config;

return function (Config $config) {

    /**
     * Enable DB sessions
     */
    $config['session.db_enable'] = false;

    /**
     * Vendor paths to look for libs we manage
     */
    $config['vendor.paths'] = ['/vendor/uom', '/vendor/ttek', '/vendor/tropotek', '/assets', '/plugin', '/html'];

    /**
     * File to ignore when we are doing a comparison from this repo to the remote repo
     */
    $config['diff.exclude.files'] = ['composer.json', 'changelog.md'];

};