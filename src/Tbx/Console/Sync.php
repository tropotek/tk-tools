<?php
namespace Tbx\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class Sync extends Iface
{
    
    /**
     *
     */
    protected function configure()
    {
        $this->setName('sync')
            ->setAliases(array('syn'))
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'The path to save the archive.', $this->getCwd())
            ->setDescription('Use this script to backup all sites and DB to a folder');
    }

    /**
     * @param \Tbx\Console\ArgvInput $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $timeStart = microtime(true);
        parent::execute($input, $output);

        $path = $input->getOption('path');

        // Backup DB
        $hostList = array(
            '252s-live.vet.unimelb.edu.au' => array(
                'dbType' => 'mysql',
                'dbUser' => 'backup',
                'dbPass' => '__bak_~3200901',
                'files' => array(
                    'root@252s-live:/home/mifsudm',
                    'root@252s-live:/home/ems',
                    'root@252s-live:/home/voce',
                    'root@252s-live:/home/skills',
                    'root@252s-live:/home/smms',
                    'root@252s-live:/home/stats',
                    'root@252s-live:/home/tad',
                    'root@252s-live:/home/tis',
                    //'root@252s-live:/home/www'
                )
            ),
            '252s-weblive.vet.unimelb.edu.au' => array(
                'dbType' => 'mysql',
                'dbUser' => 'backup',
                'dbPass' => '__bak_~3200901',
                'files' => array(
                    'root@252s-weblive:/home/mifsudm',
                    'root@252s-weblive:/home/redcap',
                    'root@252s-weblive:/home/tkWiki',
                    'root@252s-weblive:/home/ehallein',
                    'root@252s-weblive:/home/avian',
                    'root@252s-weblive:/home/imagebank',
                    'root@252s-weblive:/home/photobank',
                    //'root@252s-weblive:/home/ems',
                    //'root@252s-weblive:/home/epi',
                )
            ),
        );


        foreach ($hostList as $host => $options) {
            $backupPath = $path.'/'.$host;
            if (!file_exists($backupPath))
                mkdir($backupPath, 0777, true);

            // Backup database to a daily namespace so we can have 5 days of DB backups
            $dbArchFile = $backupPath . '/' . $host.'-'.date('D') . '.tgz';
            if (!is_file($dbArchFile) || (time() - filemtime($dbArchFile)) >= 60*60*6) {
                $cmd = sprintf('tk dbBackup -H %s -U %s -P %s -T %s -p %s -B %s',
                    $host, $options['dbUser'], $options['dbPass'], $options['dbType'], $backupPath, $host.'-'.date('D')
                );
                $this->write('Backup Database: ' . $dbArchFile);
                $this->writeComment($cmd, OutputInterface::VERBOSITY_VERBOSE);
                system('  ' . $cmd);
            } else {
                $this->write('Backup Exists: ' . $dbArchFile, OutputInterface::VERBOSITY_VERBOSE);
            }

            // Sync files
            foreach ($options['files'] as $srcPath) {
                $archPath = $backupPath . '/' . basename($srcPath);
                $cmd = sprintf('rsync -azhe ssh %s %s', $srcPath, $backupPath);
                $this->write('  Backup files: ' . $archPath);
                $this->writeComment('    ' . $cmd, OutputInterface::VERBOSITY_VERBOSE);
                system($cmd);
                //break;
            }
        }

        // Display Script End time
        $timeEnd = microtime(true);
        $executionTime = ($timeEnd - $timeStart);
        $this->write('Total Execution Time: ' . $this->formatTime($executionTime));
    }


    /**
     * @param $t
     * @param string $f
     * @return string
     */
    function formatTime($t,$f=':') // t = seconds, f = separator
    {
        return sprintf("%02d%s%02d%s%02d", floor($t/3600), $f, ($t/60)%60, $f, $t%60);
    }


}
