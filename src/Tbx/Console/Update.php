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
class Update extends Iface
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('update')
            ->setAliases(array('up'))
            ->addOption('noLibs', 'X', InputOption::VALUE_NONE, 'Do not update the ttek libs.')
            ->addOption('dryRun', 'D', InputOption::VALUE_NONE, 'Test how the update would run without uploading changes.')
            ->setDescription("Run from the root of a ttek project to update the repository.");
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

        $vcs = \Tbx\Git::create($this->getCwd(), $input->getOption('dryRun'));
        $vcs->setInputOutput($input, $output);
        $this->writeStrongInfo(ucwords($this->getName()) . ': ' . basename($vcs->getPath()));

        $vcs->update();

        if ($input->getOption('noLibs') || !count(\Tbx\Git::$VENDOR_PATHS)) return;
        foreach (\Tbx\Git::$VENDOR_PATHS as $vPath) {
            $libPath = rtrim($vcs->getPath(), '/') . $vPath;
            if (is_dir($libPath)) {      // If vendor path exists
                foreach (new \DirectoryIterator($libPath) as $res) {
                    if ($res->isDot() || substr($res->getFilename(), 0, 1) == '_') continue;
                    $path = $res->getRealPath();
                    if (!$res->isDir() && !is_dir($path.'/.git')) continue;

                    try {
                        $v = \Tbx\Git::create($path, $input->getOption('dryRun'));
                        $v->setInputOutput($input, $output);
                        $this->writeInfo(ucwords($this->getName()) . ': ' . basename($v->getPath()));
                        $v->update();
                    } catch (\Exception $e) {
                        $this->writeError($e->getMessage());
                    }
                }
            }
        }

    }

}