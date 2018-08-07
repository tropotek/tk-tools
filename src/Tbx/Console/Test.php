<?php
namespace Tbx\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class Test extends Iface
{
    
    /**
     *
     */
    protected function configure()
    {
        $this->setName('test')
            ->setDescription('This is a test script only');
    }

    /**
     * @param \Tbx\Console\ArgvInput $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $this->writeInfo(ucwords($this->getName()));

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





    }



}
