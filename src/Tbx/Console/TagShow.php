<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Tropotek <info@tropotek.com>
 */
class TagShow extends Iface
{

    protected function configure()
    {
        $this->setName('tagShow')
            ->setAliases(array('tags', 'ts'))
            ->addOption('nextTag', 't', InputOption::VALUE_NONE, 'Display next release tag if this project is to be released.')
            ->addOption('noLibs', 'X', InputOption::VALUE_NONE, 'Do not show the ttek libs.')
            ->setDescription("Run from the root of a ttek project.");
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!\Tbx\Git::isGit($this->getCwd())) {
            throw new \Tk\Exception('Not a GIT repository: ' . $this->getCwd());
        }

        $sformat = '<info>%-25s</info> <comment>%-12s %-12s</comment>';
        $this->getOutput()->writeln(sprintf('<fg=magenta>%-25s</> <fg=magenta>%-12s %-12s</>', 'package', 'curr', 'next'));

        $vcs = \Tbx\Git::create($this->getCwd(), $input->getOptions());
        $vcs->setInputOutput($input, $output);
        $tag = $vcs->getCurrentTag($vcs->getBranchAlias());
        $nextTag = $vcs->lookupNextTag($tag);
        $this->getOutput()->writeln(sprintf($sformat, $vcs->getName(), $tag, $nextTag));

        if ($input->getOption('noLibs') || !count($this->getVendorPaths())) return Command::FAILURE;
        foreach ($this->getConfig()->get('vendor.paths') as $vPath) {
            $libPath = rtrim($vcs->getPath(), '/') . $vPath;
            if (is_dir($libPath)) {      // If vendor path exists
                foreach (new \DirectoryIterator($libPath) as $res) {
                    if ($res->isDot() || str_starts_with($res->getFilename(), '_')) continue;
                    $path = $res->getRealPath();
                    if (!$res->isDir() || !\Tbx\Git::isGit($path)) continue;
                    try {
                        $v = \Tbx\Git::create($path, $input->getOptions());
                        $v->setInputOutput($this->getInput(), $this->getOutput());
                        $tag = $v->getCurrentTag($v->getBranchAlias());
                        $nextTag = $v->lookupNextTag($tag);
                        if ($tag == $nextTag)
                            $nextTag = '';
                        $this->getOutput()->writeln(sprintf($sformat, $v->getName(), $tag, $nextTag));
                    } catch (\Exception $e) {
                        $this->writeError($e->getMessage());
                    }
                }
            }
        }
        return Command::SUCCESS;
    }

}
