<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setInput($input);
        $this->setOutput($output);

        $output->writeln('' . $this->getName());
        $output->writeln('');

//        OutputInterface::VERBOSITY_QUIET        = 16;
//        OutputInterface::VERBOSITY_NORMAL       = 32;       // Same as 0;
//        OutputInterface::VERBOSITY_VERBOSE      = 64;
//        OutputInterface::VERBOSITY_VERY_VERBOSE = 128;
//        OutputInterface::VERBOSITY_DEBUG        = 256;


        // green text
        $output->writeln('<info>foo</info>', OutputInterface::VERBOSITY_NORMAL);
        // yellow text
        $output->writeln('<comment>foo</comment>', OutputInterface::VERBOSITY_NORMAL);
        // black text on a cyan background
        $output->writeln('<question>foo</question>', OutputInterface::VERBOSITY_NORMAL);
        // white text on a red background
        $output->writeln('<error>foo</error>', OutputInterface::VERBOSITY_NORMAL);





    }



}
