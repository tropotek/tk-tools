<?php
namespace Tbx\Console;

use Symfony\Component\Console\Input\InputDefinition;

/**
 * @author Tropotek <info@tropotek.com>
 */
class ArgvInput extends \Symfony\Component\Console\Input\ArgvInput
{
    /**
     * The name of the ini file that can be placed in the users home dir
     */
    public static string $INI_FILE = '.tkrc';

    private array $ini = [];

    /**
     * ArgvInput constructor.
     */
    public function __construct(array $argv = null, InputDefinition $definition = null)
    {
        parent::__construct($argv, $definition);

        $iniFile = \Tbx\Util::getHomePath() . '/' . self::$INI_FILE;
        if (is_file($iniFile)) {
            $this->ini = parse_ini_file($iniFile, true);
        }
    }

    public function getIniOption(string $name): mixed
    {
        if ($this->hasIniOption($name))
            return $this->ini[$name];
        return '';
    }

    public function hasIniOption(string $name): bool
    {
        return isset($this->ini[$name]);
    }

    public function getIniOptions(): array
    {
        return $this->ini;
    }

}