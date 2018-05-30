<?php
namespace Tbx\Console;

use Symfony\Component\Console\Input\InputDefinition;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */
class ArgvInput extends \Symfony\Component\Console\Input\ArgvInput
{
    /**
     * The name of the ini file that can be placed in the users home dir
     * @var string
     */
    public static $INI_FILE = '.tkrc';

    /**
     * @var array
     */
    private $ini = array();

    /**
     * ArgvInput constructor.
     * @param array|null $argv
     * @param InputDefinition|null $definition
     */
    public function __construct(array $argv = null, InputDefinition $definition = null)
    {
        parent::__construct($argv, $definition);

        $iniFile = \Tbx\Util::getHomePath() . '/' . self::$INI_FILE;
        if (is_file($iniFile)) {
            $this->ini = parse_ini_file($iniFile, true);
            if (!is_array($this->ini)) $this->ini = array();
        }
    }

    /**
     * @param $name
     * @return mixed|string
     */
    public function getIniOption($name)
    {
        if ($this->hasIniOption($name))
            return $this->ini[$name];
        return '';
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasIniOption($name)
    {
        return isset($this->ini[$name]);
    }

    /**
     * @return array
     */
    public function getIniOptions()
    {
        return $this->ini;
    }

}