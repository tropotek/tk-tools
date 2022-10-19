<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * @author Tropotek <info@tropotek.com>
 */
class Upgrade extends Iface
{

    protected function configure()
    {
        $this->setName('upgrade')
            ->setAliases(array('ug'))
            ->setDescription('Call this to upgrade a ttek project its newest tagged release.');
    }

    /**
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);
        $this->writeInfo(ucwords($this->getName()) . ': ' . basename($this->getCwd()));
        $this->writeGreenStrong('Notice: For ttek libs use the upgrade command in the projects /bin folder.');

        $cmdList = array(
            'git reset --hard',
            'git checkout master',
            'git pull',
            'git log --tags --simplify-by-decoration --pretty="format:%ci %d %h"',
            'git checkout {tag}',
            'composer update'
        );

        if ($this->getConfig()->isDebug()) {
            throw new \Tk\Exception('Projects should not be upgraded in debug mode');
//            array_unshift($cmdList, 'ci');
//            $cmdList[] = 'git reset --hard';
//            $cmdList[] = 'git checkout master';
//            $cmdList[] = 'composer update';
        }

        $tag = '';
        $output = array();
        foreach ($cmdList as $i => $cmd) {
            unset($output);
            if (preg_match('/^git log /', $cmd)) {      // find tag version
                exec($cmd . ' 2>&1', $output, $ret);
                foreach ($output as $line) {
                    if (preg_match('/\(tag\: ([0-9\.]+)\)/', $line, $regs)) {
                        $tag = $regs[1];
                        break;
                    }
                }
                if (!$tag) {
                    $this->writeError('Error: Cannot find version tag.');
                    return Command::FAILURE;
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

        return Command::SUCCESS;
    }

}
