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
        $this->setInput($input);
        $this->setOutput($output);


        $vcs = \Tbx\Git::create(getcwd(), $input->getOption('dryRun'));
        $vcs->setInputOutput($input, $output);

        $this->writeInfo(ucwords($this->getName()) . ': ' . basename($vcs->getPath()));

        $message = $input->getOption('message');
        $vcs->commit($message);

        if ($input->getOption('noLibs') || !count(\Tbx\Git::$VENDOR_PATHS)) {
            return;
        }

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
                        $vcs->commit($message);
                    } catch (\Exception $e) {
                        $this->writeError($e->getMessage());
                    }
                }
            }
        }


    }



}
