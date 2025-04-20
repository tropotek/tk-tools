<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Tropotek <info@tropotek.com>
 */
class Update extends Iface
{

    protected function configure(): void
    {
        $this->setName('update')
            ->setAliases(array('up'))
            ->addOption('noLibs', 'X', InputOption::VALUE_NONE, 'Do not update the ttek libs.')
            ->addOption('dryRun', 'D', InputOption::VALUE_NONE, 'Test how the update would run without uploading changes.')
            ->setDescription("Run from the root of a ttek project to update the repository and ttek libs.");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sp = '%s: %-18s %s';

        if (!\Tbx\Git::isGit((string)getcwd()))
            throw new \Tk\Exception('Not a GIT repository: ' . getcwd());
        $vcs = \Tbx\Git::create((string)getcwd(), $input->getOptions());
        $vcs->setInputOutput($input, $output);

        $s = sprintf($sp, ucwords($this->getName()), basename($vcs->getPath()), '{' . $vcs->getCurrentBranch() . '}');
        $this->writeStrongInfo($s);

        $vcs->update();

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
                        $s = sprintf($sp, ucwords($this->getName()), basename($v->getPath()), '{'.$v->getCurrentBranch().'}');
                        $this->writeInfo($s);
                        $v->update();
                    } catch (\Exception $e) {
                        $this->writeError($e->getMessage());
                    }
                }
            }
        }
        return Command::SUCCESS;
    }

}
