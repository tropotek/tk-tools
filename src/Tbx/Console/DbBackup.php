<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tk\Db;

/**
 * @author Tropotek <https://tropotek.com/>
 */
class DbBackup extends Iface
{

    protected function configure()
    {
        $this->setName('dbBackup')
            ->setAliases(['db'])
            ->addOption('user', 'U', InputOption::VALUE_OPTIONAL, 'The database username', 'dev')
            ->addOption('pass', 'P', InputOption::VALUE_OPTIONAL, 'The database password', 'dev007')
            ->addOption('host', 'H', InputOption::VALUE_OPTIONAL, 'The database password', 'localhost')
            ->addOption('port', 'O', InputOption::VALUE_OPTIONAL, 'The server port', 3306)
            ->addOption('type', 'M', InputOption::VALUE_OPTIONAL, 'The database type', 'mysql')
            ->addOption('dbName', 'N', InputOption::VALUE_OPTIONAL, 'The database name to export, if none then all available databases to the user are exported', '')
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'The path to save the archive', getcwd())
            ->addOption('backupName', 'B', InputOption::VALUE_OPTIONAL, 'the name of the archive', '')
            ->setDescription('Backup all tables in a DB');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options = $input->getOptions();

        $backupName = $options['backupName'] ?? '';
        if ( !is_string($backupName)) $backupName = '';

        if (empty($backupName)) {
            $timestamp = date('Ymd');
            $backupName = $input->getOption('dbName') . '-db-' . $timestamp;
        }

        $tempPath = sys_get_temp_dir().'/tk-dbBackup-'.getmyuid();
        if ($input->getOption('dbName')) {
            $tempPath = sys_get_temp_dir() . '/' . $input->getOption('dbName') . '-' . getmyuid();
        }

        $backupDir = $tempPath . '/' . $backupName;
        $archivePath = $tempPath . '/' . $backupName . '.tgz';

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $exclude = ['Database', 'information_schema', 'performance_schema', 'phpmyadmin', 'mysql', 'dbispconfig', 'roundcube'];
        $databaseList = [$input->getOption('dbName')];

        $db = Db::connect(Db::toDsn($options));

        if (!$input->getOption('dbName')) {
            $dbs = $db->query('SHOW DATABASES');
            if ($dbs !== false) {
                $databaseList = $dbs->fetchAll(\PDO::FETCH_COLUMN, 0);
            }
        }

        foreach ($databaseList as $dbName) {
            if (in_array($dbName, $exclude)) continue;
            $this->writeStrong($dbName, OutputInterface::VERBOSITY_VERBOSE);
            if (false !== $db->exec('USE '.$dbName)) {
                \Tk\Db\DbBackup::save($backupDir.'/'.$dbName.'.sql', $options);
            }
        }

        $cmd = sprintf('cd %s && tar zcf %s %s ', $tempPath, basename($archivePath), basename($backupDir));
        $this->writeComment($cmd,OutputInterface::VERBOSITY_VERBOSE);
        system($cmd);

        $path = $options['path'];
        if (!is_string($path)) $path = '';
        $cmd = sprintf('mv %s %s ', $archivePath, $path);
        $this->writeComment($cmd,OutputInterface::VERBOSITY_VERBOSE);
        system($cmd);

        $this->write($path.'/'.basename($archivePath));
        \Tk\FileUtil::rmdir($tempPath);

        return Command::SUCCESS;
    }

}
