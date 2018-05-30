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
class TagProject extends Iface
{

    /*
      Tag a release from the repository. Works only on checked out projects.
      This command will search the search the project for all packages
      in use and tag and release them with new version along with the
      parent project.
    */


    /**
     *
     */
    protected function configure()
    {
        $this->setName('tagProject')
            ->setAliases(array('tp'))
            ->addOption('static', 'c', InputOption::VALUE_NONE, 'If set, then the existing composer.json \'require \' versions are updated to use specific versions of the libs EG: ~1.0 becomes 1.0.6 for example.')
            ->addOption('notStable', 's', InputOption::VALUE_NONE, 'Default stable(even) version numbers (1.0.2, 1.0.4, etc). Set to enable odd version increments (1.0.1, 1.0.3, etc).')
            //->addOption('json', 'j', InputOption::VALUE_NONE, 'Show the composer.json to stdout on completion.')
            ->addOption('forceTag', 'f', InputOption::VALUE_NONE, 'Forces a tag version even if there is no change from the previous version.')

            ->addOption('noLibs', 'X', InputOption::VALUE_NONE, 'Do not tag vendor ttek libs.')
            ->addOption('dryRun', 'D', InputOption::VALUE_NONE, 'Test how the commit would run without uploading changes.')
            ->setDescription('Tag a release from the repository.');
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

        $vcs = \Tbx\Git::create(getcwd(), $input->getOption('dryRun'));
        $vcs->setInputOutput($input, $output);
        $curVer = $vcs->getCurrentTag();
        if (!$curVer) $curVer = '0.0.0';

        $this->writeInfo(ucwords($this->getName()) . ': ' . basename($vcs->getPath()));
        $this->write('Curr Ver: ' . $curVer);
        $this->write('Remote Origin: ' . $vcs->getUri());

        // Tag Project
        if ($vcs->isDiff($curVer)) {
            $composerFile = $vcs->getPath() . '/composer.json';
            $composerJson = '';
            if (is_file($vcs->getPath() . '/composer.json')) {
                $composerJson = json_decode(file_get_contents($composerFile));
                if ($composerJson->type != 'project') {
                    throw new \Exception('Only `project` package types can be released.');
                }
                $composerJson->{'minimum-stability'} = 'stable';
                if (!$input->getOption('dryRun')) {
                    file_put_contents($composerFile, \Tbx\Util::jsonPrettyPrint(json_encode($composerJson)));
                }
            }

            $version = $vcs->tagRelease($input->getOptions());
            if (version_compare($version, $curVer, '>')) {
                $this->write('New Version: ' . $version);
                $this->write('Changelog: ' . $vcs->getChangelog(), OutputInterface::VERBOSITY_VERY_VERBOSE);
            } else {
                $this->write('Nothing To Tag');
            }

            if ($composerJson) {
                $composerJson->{'minimum-stability'} = 'dev';
                if (!$input->getOption('dryRun')) {
                    file_put_contents($composerFile, \Tbx\Util::jsonPrettyPrint(json_encode($composerJson)));
                    $vcs->commit();
                }
            }
        }

        if ($input->getOption('noLibs') || !count(\Tbx\Git::$VENDOR_PATHS)) return;
        foreach (\Tbx\Git::$VENDOR_PATHS as $vPath) {
            $vendorPath = rtrim($vcs->getPath(), '/') . $vPath;
            if (!is_dir($vendorPath)) continue;
            foreach (new \DirectoryIterator($vendorPath) as $res) {
                if ($res->isDot() || substr($res->getFilename(), 0, 1) == '_') continue;
                $path = $res->getRealPath();
                if (!$res->isDir() && !is_dir($path.'/.git')) continue;
                try {
                    $v = \Tbx\Git::create($path, $input->getOption('dryRun'));
                    $v->setInputOutput($input, $output);
                    $curVer = $v->getCurrentTag();
                    if (!$curVer) $curVer = '0.0.0';
                    $this->writeInfo(ucwords($this->getName()) . ': ' . basename($v->getPath()));
                    $this->write('Curr Ver: ' . $curVer);
                    $version = $v->tagRelease($input->getOptions());
                    if (version_compare($version, $curVer, '>')) {
                        $this->write('New Version: ' . $version);
                        $this->write('Changelog: ' . $vcs->getChangelog(), OutputInterface::VERBOSITY_VERY_VERBOSE);
                    } else {
                        $this->write('Nothing To Tag');
                    }
                } catch (\Exception $e) {
                    $this->writeError($e->getMessage());
                }
            }
        }



    }

}
