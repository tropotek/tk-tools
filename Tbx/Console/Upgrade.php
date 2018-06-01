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
class Upgrade extends Iface
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
        $this->setName('upgrade')
            ->setAliases(array('ug'))
            ->setDescription('Call this to upgrade s ttek project its newest tagged release.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setInput($input);
        $this->setOutput($output);
        $this->writeInfo(ucwords($this->getName()) . ': ' . basename(getcwd()));


        $config = \Tk\Config::getInstance();

        $cmdList = array(
            'git reset --hard',
            'git checkout master',
            'git pull',
            'git log --tags --simplify-by-decoration --pretty="format:%ci %d %h"',
            'git checkout {tag}',
            'composer update'
        );

        if ($config->isDebug()) {
            array_unshift($cmdList, 'ci');
            $cmdList[] = 'git reset --hard';
            $cmdList[] = 'git checkout master';
            $cmdList[] = 'composer update';
        }


        $tag = '';
        $output = array();
        foreach ($cmdList as $i => $cmd) {
            unset($output);
            if (preg_match('/^git log /', $cmd)) {      // find tag version
                //$this->writeInfo($cmd);
                exec($cmd . ' 2>&1', $output, $ret);
                foreach ($output as $line) {
                    if (preg_match('/\(tag\: ([0-9\.]+)\)/', $line, $regs)) {
                        $tag = $regs[1];
                        //$this->writeComment('Checking Out: ' . $tag);
                        break;
                    }
                }
                if (!$tag) {
                    // Exit there
                    $this->writeError('Error: Cannot find version tag.');
                    return;
                }
            } else {
                if ($tag) {
                    $cmd = str_replace('{tag}', $tag, $cmd);
                }
                $this->writeInfo($cmd);
                if (preg_match('/^composer /', $cmd)) {
                    system($cmd);
                } else {
                    exec($cmd . ' 2>&1', $output, $ret);
                    if ($cmd == 'ci') {
                        continue;
                    }
                    $this->writeComment(implode("\n  ", $output));
                }
            }
        }

    }



}
