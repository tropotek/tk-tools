<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Tropotek <info@tropotek.com>
 */
class Test extends Iface
{
    
    /**
     *
     */
    protected function configure()
    {
        $this->setName('test')
            ->setAliases(array('t'))
            ->setDescription('This is a test script only');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->getConfig()->isDebug()) {
            $this->writeError('Error: Only run this command in a debug environment.');
            return Command::FAILURE;
        }

        $options = $input->getOptions();
        $arguments = $input->getArguments();
        $iniOptions = $input->getIniOptions();

        $this->writeRed('writeRed');
        $this->writeGrey('writeGrey');
        $this->writeBlue('writeBlue');
        $this->writeStrongBlue('writeStrongBlue');
        $this->writeStrong('writeStrong');
        $this->writeInfo('writeInfo');
        $this->writeStrongInfo('writeStrongInfo');
        $this->writeComment('writeComment');
        $this->writeQuestion('writeQuestion');
        $this->writeError('writeError');
        $this->write('write');


        $output->writeln('Complete!!!');
        return Command::SUCCESS;
    }



}
