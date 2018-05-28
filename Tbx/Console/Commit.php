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
class Commit extends Command
{

    /**
     * @var OutputInterface
     */
    public $output = null;

    /**
     * @var InputInterface
     */
    public $input = null;


    /**
     *
     */
    protected function configure()
    {
        $this->setName('commit')
            ->setAliases(array('ci'))
            ->setDescription("Run from the root of r ttek project to commit code changes.\nIt will iterate through all nested Tk libs and commit any changes.");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        
        // required vars
        $output->writeln('<fg=green>Hello World!!!</>');

        $output->writeln('Complete!!!');

    }



}
