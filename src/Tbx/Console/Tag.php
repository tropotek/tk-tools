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
class Tag extends Iface
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('tag')
            ->addOption('name', 't', InputOption::VALUE_OPTIONAL, 'Specify a tag version name.', '')
            ->addOption('notStable', 's', InputOption::VALUE_NONE, 'Default stable(even) version tag (1.0.2, 1.0.4, etc). Set to enable odd version increments (1.0.1, 1.0.3, etc).')
            ->addOption('forceTag', 'f', InputOption::VALUE_NONE, 'Forces a tag version even if there is no change from the previous version.')

            //->addOption('noLibs', 'X', InputOption::VALUE_NONE, 'Do not commit ttek libs.')
            ->addOption('dryRun', 'D', InputOption::VALUE_NONE, 'Test how the commit would run without uploading changes.')
            ->setDescription('Tag and release a repository.');
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
        $vcs = \Tbx\Git::create($this->getCwd(), $input->getOptions());
        $vcs->setInputOutput($input, $output);

        $curVer = $vcs->getCurrentTag($vcs->getBranchAlias());
        if (!$curVer) {
            $curVer = '0.0.0';
        }

        $this->writeInfo(ucwords($this->getName()) . ': ' . basename($vcs->getPath()));
        $this->write('Curr Ver: ' . $curVer);
        $this->write('Remote Origin: ' . $vcs->getUri());

        $version = $vcs->tagRelease($input->getOption('name'));

        if (version_compare($version, $curVer, '>')) {
            $this->write('New Tag Released');
            $this->write('Version: ' . $version);
            $this->write('Changelog: ' . $vcs->getChangelog(), OutputInterface::VERBOSITY_VERY_VERBOSE);
        } else {
            $this->write('Nothing To Tag');
        }

    }


}
