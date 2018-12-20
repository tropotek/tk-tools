<?php
namespace Tbx\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class Commit extends Iface
{

    /**
     *
     */
    protected function configure()
    {
        $this->setName('commit')
            ->setAliases(array('ci'))
            ->addArgument('message', InputArgument::OPTIONAL, 'Repository Commit Message', '')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Do not changes, just commit. Slower.')
            //->addOption('message', 'm', InputOption::VALUE_OPTIONAL, 'Repository Commit Message', '')
            ->addOption('noLibs', 'X', InputOption::VALUE_NONE, 'Do not commit ttek libs.')
            ->addOption('dryRun', 'D', InputOption::VALUE_NONE, 'Test how the commit would run without uploading changes.')
            ->setDescription("Run from the root of a ttek project to commit the code and ttek lib changes.");
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

        $message = $input->getArgument('message');
        $vcs->commit($message, $input->getOption('force'));

        if ($input->getOption('noLibs') || !count($this->getVendorPaths())) return;
        foreach ($this->getVendorPaths() as $vPath) {
            $libPath = rtrim($vcs->getPath(), '/') . $vPath;
            if (is_dir($libPath)) {      // If vendor path exists
                foreach (new \DirectoryIterator($libPath) as $res) {
                    if ($res->isDot() || !$res->isDir() || substr($res->getFilename(), 0, 1) == '_') continue;
                    $path = $res->getRealPath();
                    try {
                        if (!\Tbx\Git::isGit($path)) continue;  // Stop unnecessary errors

                        $v = \Tbx\Git::create($path, $input->getOption('dryRun'));
                        $v->setInputOutput($input, $output);
                        $this->writeInfo(ucwords($this->getName()) . ': ' . basename($v->getPath()));
                        $v->commit($message, $input->getOption('force'));
                    } catch (\Exception $e) {
                        $this->writeError($e->getMessage());
                    }
                }
            }
        }


    }



}
