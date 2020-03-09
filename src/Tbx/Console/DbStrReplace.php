<?php
namespace Tbx\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tk\Db\Pdo;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class DbStrReplace extends Iface
{
    const TABLE_ALL = 'ALL';

    
    /**
     *
     */
    protected function configure()
    {
        $this->setName('dbStrReplace')
            ->setAliases(array('dstr', 'dbsr'))
            ->addArgument('search', InputArgument::REQUIRED, 'The string to search for.')
            ->addArgument('replace', InputArgument::REQUIRED, 'The string to replace.')

            ->addOption('table', 'T', InputOption::VALUE_OPTIONAL, 'The database table to search.', self::TABLE_ALL)
            ->addOption('confirm', 'C', InputOption::VALUE_NONE, 'Ask for confirmation on each table replace.')
            ->addOption('backup', 'B', InputOption::VALUE_NONE, 'Backup the database before replacing data.')

            ->addOption('user', 'U', InputOption::VALUE_OPTIONAL, 'The database username.', 'dev')
            ->addOption('pass', 'P', InputOption::VALUE_OPTIONAL, 'The database password.', 'dev007')
            ->addOption('host', 'H', InputOption::VALUE_OPTIONAL, 'The database password.', 'localhost')
            ->addOption('type', 'M', InputOption::VALUE_OPTIONAL, 'The database type.', 'mysql')
            ->addOption('name', 'N', InputOption::VALUE_REQUIRED, 'The database name to search and replace a string')
            ->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'The path to save the archive.', $this->getCwd())
            ->addOption('dryRun', 'D', InputOption::VALUE_NONE, 'Test if the command would work without changing the data.')
            //->addOption('backupName', 'B', InputOption::VALUE_OPTIONAL, 'the name of the archive', 'dbBackup-' . time())

            ->setDescription('Use this command to search and replace a string in a DB table or all tables');
    }

    /**
     * @param \Tbx\Console\ArgvInput $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $options = $input->getOptions();

        if (!$input->getOption('name')) {
            throw new \Tk\Exception('Please supply a valid Database name to search in');
        }

        $backupFile = '';
        if ($input->getOption('backup')) {
            $backupFile = $input->getOption('path') . '/backup-' . $options['name'] . '-' . time() . '.sql';
        }

        $dsn = 'mysql:host='.$options['host'];
        $db = Pdo::create($options);
        if ($backupFile) {
            vd($backupFile);
            \Tk\Util\SqlBackup::create($db)->save($backupFile);
            $cmd = sprintf('cd %s && tar zcf %s %s && rm -rf %s ', $input->getOption('path'), basename($backupFile . '.tgz'), basename($backupFile), basename($backupFile));
            $this->writeComment($cmd);
            system($cmd);
        }

        $tableList = $input->getOption('table');
        if ($tableList != self::TABLE_ALL && !is_array($tableList)) {
            $tableList = array($tableList);
        } else {
            $tableList = $db->getTableList();
        }

        foreach ($tableList as $table) {
            if ($table[0] == '_') continue;
            $info = $db->getTableInfo($table);

            // TODO: get the number of occurrences
            $foundData = array();

            //vd($info);
            foreach ($info as $column => $data) {
                if (!preg_match('/(text|char)/', $data['Type'])) continue;
                if ($data['Key'] == 'PRI') continue;
                $sql = sprintf('SELECT COUNT(*) as i FROM `%s` WHERE  `%s` LIKE %s;',
                    $table, $column, $db->quote('%'.$input->getArgument('search').'%') );
                $this->writeComment($sql, OutputInterface::VERBOSITY_VERY_VERBOSE);
                //\Tk\log::notice($sql);
                $i = $db->query($sql)->fetchColumn();
                $foundData[$column] = $i;
            }
            if (!$this->countFinds($foundData)) {
                continue;
            }
            vd($foundData);


            $this->writeInfo('  Replacing...');
            //vd($info);
            foreach ($info as $column => $data) {
                if (!preg_match('/(text|char)/', $data['Type'])) continue;
                if ($data['Key'] == 'PRI') continue;
                if (empty($foundData[$column])) continue;
                if ($input->getOption('confirm') && !$this->askConfirmation('Are you sure you want to replace string `'.$input->getArgument('search').'` with `'.$input->getArgument('replace').
                        '` in '.$foundData[$column].' columns of the column `'.$table.'.'.$column.'`? ')) continue;

                $sql = sprintf('UPDATE `%s` SET `%s` = REPLACE(`%s`, %s, %s) WHERE  `%s` LIKE %s;',
                    $table, $column, $column, $db->quote($input->getArgument('search')), $db->quote($input->getArgument('replace')),
                    $column, $db->quote('%'.$input->getArgument('search').'%'));
                //\Tk\log::notice($sql);
                $this->writeComment($sql, OutputInterface::VERBOSITY_VERY_VERBOSE);
                $r = $db->exec($sql);
                \Tk\Log::warning('Updates: ' . $r);
            }

            $this->writeInfo('  Done.');

        }

/**

        foreach ($databaseList as $dbName) {
            if (in_array($dbName, $exclude)) continue;
            $this->writeStrong($dbName);
            $options['name'] = $dbName;
            $db = \Tk\Db\Pdo::create($options);
            \Tk\Util\SqlBackup::create($db)->save($backupFile.'/'.$dbName.'.sql');
        }

        $cmd = sprintf('cd %s && tar zcf %s %s ', $tempPath, basename($archivePath), basename($backupFile));
        $this->writeComment($cmd);
        system($cmd);

        $cmd = sprintf('mv %s %s ', $archivePath, $options['path']);
        $this->writeComment($cmd);
        system($cmd);

        \Tk\File::rmdir($tempPath);
*/

        /*

        $this->writeInfo(ucwords($this->getName()));

        $options = $input->getOptions();
        $arguments = $input->getArguments();
        $iniOptions = $input->getIniOptions();

        $this->writeRed('writeRed');
        $this->writeGrey('writeGrey');
        $this->writeBlue('writeBlue');
        $this->writeStrongBlue('writeStrongBlue');
        $this->writeStrong('writeStrong');
        $this->writeInfo('writeInfo');
        $this->writeStrongInfo('writeStrongInfo');
        $this->writeComment('writeComment');
        $this->writeQuestion('writeQuestion');
        $this->writeError('writeError');
        $this->write('write');

        */



    }

    /**
     * @param $data
     * @return int
     */
    public function countFinds($data)
    {
        $t = 0;
        foreach ($data as $k => $v) {
            $t += $v;
        }
        return $t;
    }


}
