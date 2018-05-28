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

        $vcs = \Tbx\Git::create(getcwd(), $input->getOption('dryRun'));
        $vcs->setInputOutput($input, $output);

        $output->writeln(ucwords($this->getName()) . ': ' . basename($vcs->getPath()));


        $vcs->update();
        $this->output->writeln('', OutputInterface::VERBOSITY_NORMAL);

        if ($input->getOption('noLibs') || !count(\Tbx\Git::$VENDOR_PATHS)) {
            return;
        }

        foreach (\Tbx\Git::$VENDOR_PATHS as $vPath) {
            $libPath = rtrim($vcs->getPath(), '/') . $vPath;
            if (is_dir($libPath)) {      // If vendor path exists
                foreach (new \DirectoryIterator($libPath) as $res) {
                    if ($res->isDot() || substr($res->getFilename(), 0, 1) == '_') {
                        continue;
                    }
                    $path = $res->getRealPath();
                    if (!$res->isDir() && !is_dir($path.'/.git')) {
                        continue;
                    }


                    try {
                        $vcs->setPath($path);
                        $vcs->setInputOutput($input, $output);
                        $output->writeln(ucwords($this->getName()) . ': ' . basename($vcs->getPath()));
                        $vcs->update();
                    } catch (\Exception $e) {
                        $output->writeln('<error>'.$e->getMessage().'</error>');
                    }
                    $this->output->writeln('', OutputInterface::VERBOSITY_NORMAL);
//                    // TODO: Do not call the CLI command iterate using PHP
//                    $cmd = sprintf('cd %s && %s', escapeshellarg($path), $_SERVER['argv'][0] . ' ' . $input->getFirstArgument());
//                    system($cmd);
                }
            }
        }


    }



}
