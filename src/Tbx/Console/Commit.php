<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Tropotek <info@tropotek.com>
 */
class Commit extends Iface
{

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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sp = '%s: %-18s %s';
vd($this->getCwd());
        $vcs = \Tbx\Git::create($this->getCwd(), $input->getOptions());
        $vcs->setInputOutput($input, $output);
        $s = sprintf($sp, ucwords($this->getName()), basename($vcs->getPath()), '{' . $vcs->getCurrentBranch() . '}');
        $this->writeStrongInfo($s);

        $message = $input->getArgument('message');
        $vcs->commit($message, $input->getOption('force'));

        if ($input->getOption('noLibs') || !count($this->getVendorPaths())) return Command::FAILURE;
        foreach ($this->getVendorPaths() as $vPath) {
            $libPath = rtrim($vcs->getPath(), '/') . $vPath;
            if (is_dir($libPath)) {      // If vendor path exists
                foreach (new \DirectoryIterator($libPath) as $res) {
                    if ($res->isDot() || !$res->isDir() || str_starts_with($res->getFilename(), '_')) continue;
                    $path = $res->getRealPath();
                    try {
                        if (!\Tbx\Git::isGit($path)) continue;  // Stop unnecessary errors

                        $v = \Tbx\Git::create($path, $input->getOptions());
                        $v->setInputOutput($input, $output);
                        $s = sprintf($sp, ucwords($this->getName()), basename($v->getPath()), '{'.$v->getCurrentBranch().'}');
                        $this->writeInfo($s);
                        $v->commit($message, $input->getOption('force'));
                    } catch (\Exception $e) {
                        $this->writeError($e->getMessage());
                    }
                }
            }
        }

        return Command::SUCCESS;
    }



}
