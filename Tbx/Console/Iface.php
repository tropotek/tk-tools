<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
abstract class Iface extends \Tk\Console\Console
{

    /**
     * @var OutputInterface
     */
    protected $output = null;

    /**
     * @var InputInterface
     */
    protected $input = null;

    /**
     * @var array
     */
    protected $vendorPaths = array();



    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param OutputInterface $output
     * @return Iface
     */
    public function setOutput($output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @return InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @param InputInterface $input
     * @return Iface
     */
    public function setInput($input)
    {
        $this->input = $input;
        return $this;
    }

    /**
     * @return array
     */
    public function getVendorPaths()
    {
        return $this->vendorPaths;
    }

    /**
     * @param array $vendorPaths
     * @return $this
     */
    public function setVendorPaths($vendorPaths)
    {
        $this->vendorPaths = $vendorPaths;
        return $this;
    }


    /**
     * @param $str
     * @param int $options
     * @return mixed
     */
    protected function writeStrong($str = '', $options = OutputInterface::VERBOSITY_NORMAL)
    {
        return $this->write(sprintf('<options=bold>%s</>', $str), $options);
    }

    /**
     * @param $str
     * @param int $options
     * @return mixed
     */
    protected function writeInfo($str = '', $options = OutputInterface::VERBOSITY_NORMAL)
    {
        return $this->write(sprintf('<info>%s</info>', $str), $options);
    }

    /**
     * @param $str
     * @param int $options
     * @return mixed
     */
    protected function writeComment($str = '', $options = OutputInterface::VERBOSITY_NORMAL)
    {
        return $this->write(sprintf('<comment>%s</comment>', $str), $options);
    }

    /**
     * @param $str
     * @param int $options
     * @return mixed
     */
    protected function writeQuestion($str = '', $options = OutputInterface::VERBOSITY_NORMAL)
    {
        return $this->write(sprintf('<question>%s</question>', $str), $options);
    }

    /**
     * @param $str
     * @param int $options
     * @return mixed
     */
    protected function writeError($str = '', $options = OutputInterface::VERBOSITY_NORMAL)
    {
        return $this->write(sprintf('<error>%s</error>', $str), $options);
    }

    /**
     * @param $str
     * @param int $options
     * @return mixed
     */
    protected function write($str = '', $options = OutputInterface::VERBOSITY_NORMAL)
    {
        if ($this->output)
            return $this->output->writeln($str, $options);
    }
}
