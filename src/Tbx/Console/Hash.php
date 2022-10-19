<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Tropotek <info@tropotek.com>
 */
class Hash extends Iface
{

    protected function configure()
    {
        $this->setName('hash')
            ->addArgument('string', InputArgument::OPTIONAL, 'The string that is to have the hash applied to it.')
            ->addOption('algorithm', 'a', InputOption::VALUE_OPTIONAL, 'Specify a hash algorithm to use.', 'md5')
            ->addOption('algoList', 'l', InputOption::VALUE_NONE, 'List the available hash() algorithms.')
            ->setDescription('Generate a hash value. (i.e. "md5", "sha256", etc..)');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('algoList')) {
            $this->writeInfo(ucwords($this->getName()));
            $str = $input->getArgument('string');
            $algo = $input->getOption('algorithm');
            if (!$input->getArgument('string')) {
                throw new \Exception('Please supply a valid string to hash');
            }
            $hash = hash($algo, $str);
            $this->writeComment('String: ' . $str);
            $this->writeComment(ucfirst($algo) . ' Hash: ' . $hash);
        } else {
            $this->writeInfo(sprintf('%-12s %3s %s', 'Hash', 'Len', 'Example'));
            $this->writeInfo(sprintf('---------------------------------------------------'));
            foreach (hash_algos() as $v) {
                    $r = hash($v, $this->getName(), false);
                    $this->writeComment(sprintf('%-12s %3d %s', $v, strlen($r), $r));
            }
        }
        return Command::SUCCESS;
    }



}
