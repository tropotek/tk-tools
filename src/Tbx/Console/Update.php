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
        $sp = '%s: %-18s %s';

        if (!\Tbx\Git::isGit($this->getCwd()))
            throw new \Tk\Exception('Not a GIT repository: ' . $this->getCwd());
        $vcs = \Tbx\Git::create($this->getCwd(), $input->getOptions());
        $vcs->setInputOutput($input, $output);
        //$this->writeStrongInfo(ucwords($this->getName()) . ': ' . basename($vcs->getPath()));
        $s = sprintf($sp, ucwords($this->getName()), basename($vcs->getPath()), '{' . $vcs->getCurrentBranch() . '}');
        $this->writeStrongInfo($s);

        $vcs->update();

        if ($input->getOption('noLibs') || !count($this->getVendorPaths())) return;
        foreach ($this->getVendorPaths() as $vPath) {
            $libPath = rtrim($vcs->getPath(), '/') . $vPath;
            if (is_dir($libPath)) {      // If vendor path exists
                foreach (new \DirectoryIterator($libPath) as $res) {
                    if ($res->isDot() || substr($res->getFilename(), 0, 1) == '_') continue;
                    $path = $res->getRealPath();
                    if (!$res->isDir() || !\Tbx\Git::isGit($path)) continue;
                    try {
                        $v = \Tbx\Git::create($path, $input->getOptions());
                        $v->setInputOutput($input, $output);
                        //$this->writeInfo(ucwords($this->getName()) . ': ' . basename($v->getPath()));
                        $s = sprintf($sp, ucwords($this->getName()), basename($v->getPath()), '{'.$v->getCurrentBranch().'}');
                        $this->writeInfo($s);
                        $v->update();
                    } catch (\Exception $e) {
                        $this->writeError($e->getMessage());
                    }
                }
            }
        }

    }

}
