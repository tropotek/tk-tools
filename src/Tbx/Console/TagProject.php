<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Tag a release from the repository. Works only on checked out projects.
 * This command will search the project for all packages
 * in use and tag and release them with new version along with the
 * parent project.
 *
 * @author Tropotek <info@tropotek.com>
 */
class TagProject extends Iface
{

    protected function configure()
    {
        $this->setName('tagProject')
            ->setAliases(array('tp'))
            ->addOption('static', 'c', InputOption::VALUE_NONE, 'If set, then the existing composer.json \'require \' versions are updated to use specific versions of the libs EG: ~1.0 becomes 1.0.6 for example.')
            ->addOption('notStable', 's', InputOption::VALUE_NONE, 'Default stable(even) version numbers (1.0.2, 1.0.4, etc). Set to enable odd version increments (1.0.1, 1.0.3, etc).')
            ->addOption('forceTag', 'f', InputOption::VALUE_NONE, 'Forces a tag version even if there is no change from the previous version.')

            ->addOption('noLibs', 'X', InputOption::VALUE_NONE, 'Do not tag vendor ttek libs.')
            ->addOption('dryRun', 'D', InputOption::VALUE_NONE, 'Test how the commit would run without uploading changes.')
            ->setDescription('Tag a release from the repository.');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!\Tbx\Git::isGit($this->getCwd()))
            throw new \Tk\Exception('Not a GIT repository: ' . $this->getCwd());

        $projectPath = rtrim($this->getCwd(), '/');

        $vcs = \Tbx\Git::create($projectPath, $input->getOptions());
        $keywords = [];
        if (!empty($vcs->getComposer()->keywords))
            $keywords = $vcs->getComposer()->keywords;
        if (in_array('tk-template', $keywords)) {
            throw new \Tk\Exception('Template projects cannot be tagged');
        }

        // Tag Libs
        if (!$input->getOption('noLibs') && count($this->getVendorPaths())) {
            foreach ($this->getVendorPaths() as $vPath) {
                $vendorPath = $projectPath . $vPath;
                if (!is_dir($vendorPath)) continue;
                foreach (new \DirectoryIterator($vendorPath) as $res) {
                    if ($res->isDot() || str_starts_with($res->getFilename(), '_')) continue;
                    $path = $res->getRealPath();
                    if (!$res->isDir() || !\Tbx\Git::isGit($path)) continue;
                    try {
                        $v = \Tbx\Git::create($path, $input->getOptions());
                        $v->setInputOutput($input, $output);
                        $curVer = $v->getCurrentTag($v->getBranchAlias());
                        if (!$curVer) $curVer = '0.0.0';

                        if ($v->isDiff($curVer)) {
                            $title = sprintf('%-11s %s', '[' . $curVer . ']', basename($v->getPath()));
                            $this->writeInfo($title);
                            $version = $v->tagRelease();
                            if (version_compare($version, $curVer, '>')) {
                                $this->write('New Version: ' . $version, OutputInterface::VERBOSITY_VERY_VERBOSE);
                                $this->writeGrey('Changelog: ' . $v->getChangelog(), OutputInterface::VERBOSITY_VERY_VERBOSE);
                            } else {
                                $this->writeGrey('Nothing To Tag', OutputInterface::VERBOSITY_VERY_VERBOSE);
                            }
                        }
                    } catch (\Exception $e) {
                        $this->writeError($e->getMessage());
                    }
                }
            }
        }

        // Tag Project
        $vcs = \Tbx\Git::create($projectPath, $input->getOptions());
        $vcs->setInputOutput($input, $output);
        $projCurVer = $vcs->getCurrentTag($vcs->getBranchAlias());
        if ($vcs->isDiff($projCurVer)) {
            $title = sprintf('%-11s %s', '['.$projCurVer.']', basename($vcs->getPath()));
            $this->writeStrongInfo($title);

            $projVersion = $vcs->tagRelease();
            if (version_compare($projVersion, $projCurVer, '>')) {
                $this->write('New Version: ' . $projVersion, OutputInterface::VERBOSITY_VERY_VERBOSE);
                $this->writeGrey('Changelog: ' . $vcs->getChangelog(), OutputInterface::VERBOSITY_VERY_VERBOSE);
            } else {
                $this->writeGrey('Nothing To Tag', OutputInterface::VERBOSITY_VERY_VERBOSE);
            }
        }
        return Command::SUCCESS;
    }

}
