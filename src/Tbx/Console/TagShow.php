<?php
namespace Tbx\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class TagShow extends Iface
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('tagShow')
            ->setAliases(array('tags', 'ts'))
            ->addOption('nextTag', 't', InputOption::VALUE_NONE, 'Display next release tag if this project is to be released.')
            ->addOption('noLibs', 'X', InputOption::VALUE_NONE, 'Do not update the ttek libs.')
            ->addOption('dryRun', 'D', InputOption::VALUE_NONE, 'Test how the update would run without uploading changes.')
            ->setDescription("Run from the root of a ttek project.");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if (!\Tbx\Git::isGit($this->getCwd()))
            throw new \Tk\Exception('Not a GIT repository: ' . $this->getCwd());

        $sformat = '<info>%-25s</info> <comment>%-12s %-12s</comment>';
        $vcs = \Tbx\Git::create($this->getCwd(), $input->getOption('dryRun'));
        $vcs->setInputOutput($input, $output);
        $projName = basename($vcs->getPath());

        $tagList = $vcs->getCurrentTags($input->getOptions());
        foreach ($tagList as $name => $list) {
            if ($input->getOption('noLibs') && $name != $projName) continue;
            $nextTag = '';
            if ($input->getOption('nextTag')) {
                $nextTag = $list['next'];
            }
            $this->getOutput()->writeln(sprintf($sformat, $name, $list['curr'], $nextTag));
        }

    }

}
