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
class BranchShow extends Iface
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('branchShow')
            ->setAliases(array('branches', 'bs'))
            ->addOption('noLibs', 'X', InputOption::VALUE_NONE, 'Do not show the ttek libs.')
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

        $sformat = '<info>%-25s</info> <comment>%-12s</comment>';
        $vcs = \Tbx\Git::create($this->getCwd(), $input->getOptions());
        $vcs->setInputOutput($input, $output);
        $this->getOutput()->writeln(sprintf($sformat, $vcs->getName(), $vcs->getCurrentBranch()));

        if ($input->getOption('noLibs') || !count($this->getVendorPaths())) return;
        foreach ($this->getConfig()->get('vendor.paths') as $vPath) {
            $libPath = rtrim($vcs->getPath(), '/') . $vPath;
            if (is_dir($libPath)) {      // If vendor path exists
                foreach (new \DirectoryIterator($libPath) as $res) {
                    if ($res->isDot() || substr($res->getFilename(), 0, 1) == '_') continue;
                    $path = $res->getRealPath();
                    if (!$res->isDir() || !\Tbx\Git::isGit($path)) continue;
                    try {
                        $v = \Tbx\Git::create($path, $input->getOptions());
                        $v->setInputOutput($this->getInput(), $this->getOutput());
                        $this->getOutput()->writeln(sprintf($sformat, $v->getName(), $v->getCurrentBranch()));
                    } catch (\Exception $e) {
                        $this->writeError($e->getMessage());
                    }
                }
            }
        }

    }

}
