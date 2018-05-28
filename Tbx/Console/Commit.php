<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

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
            ->addOption('message', 'm', InputOption::VALUE_OPTIONAL, 'Repository Commit Message', 'Minor Code Updates - ' . trim(`hostname`))
            ->addOption('noLibs', 'N', InputOption::VALUE_NONE, 'Do not commit ttek libs.')
            ->addOption('dryRun', 'd', InputOption::VALUE_NONE, 'Test how the commit would run without uploading changes.')
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
        $this->setInput($input);
        $this->setOutput($output);


        $vcs = \Tbx\Git::create($input, $output);
        if ($input->getOption('dryRun')) {
            $vcs->setDryRun();
        }
        if (!is_dir($vcs->getProjectPath() . '/.git')) {   // GIT
            throw new \Exception('This folder does not appear to be a GIT repository.');
        }

        $output->writeln(ucwords($this->getName()) . ': ' . basename($vcs->getProjectPath()));

        $message = $input->getOption('message');
        $vcs->commit($message);
        $this->output->writeln('', OutputInterface::VERBOSITY_NORMAL);

        if ($input->getOption('noLibs') || !count(\Tbx\Git::$VENDOR_PATHS)) {
            return;
        }

        foreach (\Tbx\Git::$VENDOR_PATHS as $vPath) {
            $vendorPath = rtrim($vcs->getProjectPath(), '/') . $vPath;
            if (is_dir($vendorPath)) {      // If vendor path exists
                foreach (new \DirectoryIterator($vendorPath) as $res) {
                    if ($res->isDot() || substr($res->getFilename(), 0, 1) == '_') {
                        continue;
                    }
                    $path = $res->getRealPath();
                    if (!$res->isDir() && !is_dir($path.'/.git')) {
                        continue;
                    }
                    $cmd = sprintf('cd %s && %s -N -m %s', escapeshellarg($path), basename($_SERVER['argv'][0] . ' ' . $input->getFirstArgument()), escapeshellarg($message));
                    system($cmd);
                }
            }
        }


    }



}
