<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Tropotek <info@tropotek.com>
 */
abstract class Iface extends \Tk\Console\Console
{

    protected array $vendorPaths = [];

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->setVendorPaths($this->getConfig()->get('vendor.paths', []));
    }

    public function getVendorPaths(): array
    {
        return $this->vendorPaths;
    }

    public function setVendorPaths(array $vendorPaths): static
    {
        $this->vendorPaths = $vendorPaths;
        return $this;
    }

}
