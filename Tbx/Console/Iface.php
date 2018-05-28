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
abstract class Iface extends Command
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


}
