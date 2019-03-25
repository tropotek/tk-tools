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
class DbBackup extends Iface
{
    
    /**
     *
     */
    protected function configure()
    {
        $this->setName('dbBackup')
            ->setDescription('Backup all tables in a DB');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $timestamp = date(\Tk\Date::FORMAT_ISO_DATE);
        $tempPath = sys_get_temp_dir();
        $backupDir = $tempPath . '/dbBackup-' . $timestamp;

        $dbUser = 'dev';
        $dbPass = 'dev007';
        $dbHost = 'localhost';
        $db = new \Tk\Db\Pdo($dsn, $username, $password, array());




        vd($timestamp, $tempPath);








//        $this->writeInfo(ucwords($this->getName()));
//
//        $options = $input->getOptions();
//        $arguments = $input->getArguments();
//        $iniOptions = $input->getIniOptions();
//
//        $this->writeRed('writeRed');
//        $this->writeGrey('writeGrey');
//        $this->writeBlue('writeBlue');
//        $this->writeStrongBlue('writeStrongBlue');
//        $this->writeStrong('writeStrong');
//        $this->writeInfo('writeInfo');
//        $this->writeStrongInfo('writeStrongInfo');
//        $this->writeComment('writeComment');
//        $this->writeQuestion('writeQuestion');
//        $this->writeError('writeError');
//        $this->write('write');





    }



}
