<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Tropotek <info@tropotek.com>
 */
class PassGen extends Iface
{

    protected function configure()
    {
        $this->setName('passGen')
            ->setAliases(['pg'])
            ->addOption('key', 'k', InputOption::VALUE_OPTIONAL, 'specify a date format to use as the key', '=d-m-Y=')
            ->addOption('timezone', 't', InputOption::VALUE_OPTIONAL, 'Specify a tag version name.', 'Australia/victoria')
            ->setDescription('Generate Temporary password for projects');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->writeInfo(ucwords($this->getName()));

        $tz = date_default_timezone_get();
        date_default_timezone_set($input->getOption('timezone'));
        $raw = date($input->getOption('key'), time());
        $password = hash('md5', $raw);
        date_default_timezone_set($tz);
        $this->writeComment('Password: ' . $password);

        return Command::SUCCESS;
    }

}
