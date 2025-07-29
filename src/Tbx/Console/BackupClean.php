<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tk\Config;
use Tk\Encrypt;
use Tk\Log;
use Tk\Uri;

/**
 * remove all files with a specific extension keeping the newest n number of files
 *
 * @author Tropotek <https://tropotek.com/>
 */
class BackupClean extends Iface
{

    protected string $destPath  = '';
    protected string $extension = '';
    protected int    $min       = 5;

    protected function configure(): void
    {
        $this->setName('backup-clean')
            ->setAliases(['bc'])
            ->addArgument('destPath', InputArgument::REQUIRED, 'Specify the path of the backup files.')
            ->addArgument('extension', InputArgument::REQUIRED, 'Specify a file extension to search for.')
            ->addOption('min', 'M', InputOption::VALUE_OPTIONAL, 'Number of files to keep', 5)
            ->setDescription('Remove files, keeping the newest [min] number of files with the same extension.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->destPath  = rtrim($input->getArgument('destPath'), '/\\');
        $this->extension = $input->getArgument('extension');
        $this->min       = $input->getOption('min');

        $files = glob($this->destPath.'/*.'.$this->extension);
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        foreach ($files as $i => $file) {
            if ($i < $this->min) continue;
            unlink($file);
            $this->writeComment('Deleted: ' . $file, OutputInterface::VERBOSITY_VERY_VERBOSE);
        }

        return Command::SUCCESS;
    }

}
