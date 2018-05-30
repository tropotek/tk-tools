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

    /*
Tag and release a repository project.
Currently only GIT and composer are supported.

If the code has a composer.json file with a `branch-alias`, that alias
number os prepended to the new minor number that will be created.

EG: `branch-alias`: { `dev-master`: `1.3.x-dev` }
The minor version number is found by searching the existing tags for the
next highest number. So if a version 1.3.34 was found to be the current
highest tag for the 1.3.x versions then this lib version would be 1.3.35.

If no `composer.json` file or no branch-alias exists then the svn repo
will be searched and the next highest minor version from the repository
will be created. NOTE: This gives you no control over the major version
unless supplied as a param with --version=x.x.x
     */


    /**
     *
     */
    protected function configure()
    {
        $this->setName('tag')
            ->addOption('name', 't', InputOption::VALUE_OPTIONAL, 'Specify a tag version name.', '')
            ->addOption('notStable', 's', InputOption::VALUE_NONE, 'Default stable(even) version tag (1.0.2, 1.0.4, etc). Set to enable odd version increments (1.0.1, 1.0.3, etc).')
            ->addOption('json', 'j', InputOption::VALUE_NONE, 'Show the composer.json to stdout on completion.')
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
        $this->setInput($input);
        $this->setOutput($output);

        if ($input->getOption('json')) {
            $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        }

        $vcs = \Tbx\Git::create(getcwd(), $input->getOption('dryRun'));
        $vcs->setInputOutput($input, $output);
        $curVer = $vcs->getCurrentTag();
        if (!$curVer) {
            $curVer = '0.0.0';
        }

        $this->writeInfo(ucwords($this->getName()) . ': ' . basename($vcs->getPath()));
        $this->write('Curr Ver: ' . $curVer);
        $this->write('Remote Origin: ' . $vcs->getUri());

        $version = $vcs->tagRelease($input->getOptions(), $input->getOption('name'));
        if ($input->getOption('json')) {
            $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
            $this->write($version);
            return;
        }

        if (version_compare($version, $curVer, '>')) {
            $this->write('New Tag Released');
            $this->write('Version: ' . $version);
            $this->write('Changelog: ' . $vcs->getChangelog(), OutputInterface::VERBOSITY_VERY_VERBOSE);
        } else {
            $this->write('Nothing To Tag');
            $this->write('Version: ' . $version);
        }

    }


}
