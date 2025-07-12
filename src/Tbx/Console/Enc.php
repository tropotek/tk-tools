<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tk\Encrypt;

/**
 * @author Tropotek <https://tropotek.com/>
 */
class Enc extends Iface
{

    protected function configure(): void
    {
        $this->setName('encrypt')
            ->setAliases(['enc'])
            ->addArgument('secret', InputArgument::REQUIRED, 'Specify a secret key to use for encryption.')
            ->addArgument('string', InputArgument::REQUIRED, 'The string that is to have the hash applied to it.')
            ->setDescription('Encrypt a string using a secret key.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $enc = Encrypt::create($input->getArgument('secret'));
        $str = $input->getArgument('string');

        $r = $enc->encrypt($str);
        $this->writeComment($r);

        return Command::SUCCESS;
    }



}
