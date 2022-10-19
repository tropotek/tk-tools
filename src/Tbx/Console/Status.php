<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @author Tropotek <info@tropotek.com>
 */
class Status extends Iface
{

    protected function configure()
    {
        $this->setName('status')
            ->setAliases(['st'])
            ->addOption('noLibs', 'X', InputOption::VALUE_NONE, 'Do not update the ttek libs.')
            ->addOption('dryRun', 'D', InputOption::VALUE_NONE, 'Test how the update would run without uploading changes.')
            ->setDescription("Run from the root of a ttek project to get the status of the lib and it vendor libs.");
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!\Tbx\Git::isGit($this->getCwd()))
            throw new \Tk\Exception('Not a GIT repository: ' . $this->getCwd());
        $vcs = \Tbx\Git::create($this->getCwd(), $input->getOptions());
        $vcs->setInputOutput($input, $output);
        $this->writeInfo(ucwords($this->getName()) . ': ' . basename($vcs->getPath()));

        $str = $vcs->getStatus();
        if ($str)
            $this->writeComment($str);

        if ($input->getOption('noLibs') || !count($this->getVendorPaths())) return Command::FAILURE;
        foreach ($this->getVendorPaths() as $vPath) {
            $libPath = rtrim($vcs->getPath(), '/') . $vPath;
            if (is_dir($libPath)) {      // If vendor path exists
                foreach (new \DirectoryIterator($libPath) as $res) {
                    if ($res->isDot() || str_starts_with($res->getFilename(), '_')) continue;
                    $path = $res->getRealPath();
                    if (!$res->isDir() || !\Tbx\Git::isGit($path)) continue;
                    try {
                        $v = \Tbx\Git::create($path, $input->getOptions());
                        $v->setInputOutput($input, $output);
                        $this->writeInfo(ucwords($this->getName()) . ': ' . basename($v->getPath()));
                        $str = $v->getStatus();
                        if ($str)
                            $this->writeComment($str);
                    } catch (\Exception $e) {
                        $this->writeError($e->getMessage());
                    }
                }
            }
        }

        return Command::SUCCESS;
    }

}
