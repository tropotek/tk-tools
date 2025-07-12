<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Tk\Config;

/**
 * @author Tropotek <https://tropotek.com/>
 */
class Update extends Iface
{

    protected function configure(): void
    {
        $this->setName('update')
            ->setAliases(array('up'))
            ->addOption('noLibs', 'X', InputOption::VALUE_NONE, 'Do not update the ttek libs.')
            ->addOption('dryRun', 'D', InputOption::VALUE_NONE, 'Test how the update would run without uploading changes.')
            ->setDescription("Run from the root of a ttek project to update the repository and ttek libs. Run from the projects root to update all tk v8.0 projects");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sp = '%s: %-18s %s';

        $cwd = (string)getcwd();

        if (!\Tbx\Git::isGit($cwd)) {
            $list = scandir($cwd);
            foreach ($list as $file) {
                if (!in_array($file, Config::getValue('tk.projects', []))) continue;
                $path = $cwd.'/'.$file;
                if (!\Tbx\Git::isGit($path)) {
                    $this->writeInfo("{$path} not a git repository");
                    continue;
                }

                // update project repos
                $cmd = sprintf('cd %s && tk up', escapeshellarg($path));
                $this->write($cmd, OutputInterface::VERBOSITY_VERBOSE);
                passthru($cmd, $ret);
                echo PHP_EOL;
            }

            return Command::SUCCESS;
        }

        // update project and libs
        $vcs = \Tbx\Git::create($cwd, $input->getOptions());
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
