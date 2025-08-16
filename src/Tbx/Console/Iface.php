<?php
namespace Tbx\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Tk\Config;

/**
 * @author Tropotek <https://tropotek.com/>
 */
abstract class Iface extends Command
{

    protected ?OutputInterface $output = null;
    protected ?InputInterface $input   = null;
    protected array $vendorPaths = [];

    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->setVendorPaths($this->getConfig()->get('vendor.paths', []));
    }

    /**
     * Initializes the command just after the input has been validated.
     *
     * This is mainly useful when a lot of commands extends one main command
     * where some things need to be initialized based on the input arguments and options.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
        //$this->writeInfo($this->getName());
    }

    public function getVendorPaths(): array
    {
        return $this->vendorPaths;
    }

    public function setVendorPaths(array $vendorPaths): static
    {
        $this->vendorPaths = $vendorPaths;
        return $this;
    }

    public function getConfig(): Config
    {
        return Config::instance();
    }

    public function getOutput(): ?OutputInterface
    {
        return $this->output;
    }

    public function getInput(): ?InputInterface
    {
        return $this->input;
    }

    protected function askConfirmation(string $msg, bool $default = false): bool
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion($msg, $default);
        /** @phpstan-ignore-next-line */
        return $helper->ask($this->getInput(), $this->getOutput(), $question);
    }

    public function writeRed(string $str = '', int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<fg=red>%s</>', $str), $options);
    }

    public function writeGrey(string $str = '', int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<fg=white>%s</>', $str), $options);
    }

    public function writeBlue(string $str = '', int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<fg=blue>%s</>', $str), $options);
    }

    public function writeStrongBlue(string $str = '', int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<fg=blue;options=bold>%s</>', $str), $options);
    }

    public function writeGreen(string $str = '', int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<fg=green>%s</>', $str), $options);
    }

    public function writeGreenStrong(string $str = '', int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<fg=green;options=bold>%s</>', $str), $options);
    }

    public function writeStrong(string $str = '', int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<options=bold>%s</>', $str), $options);
    }

    public function writeInfo(string $str = '', int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<info>%s</info>', $str), $options);
    }

    public function writeStrongInfo(string $str = '', int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<fg=green;options=bold>%s</>', $str), $options);
    }

    public function writeComment(string $str = '', int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<comment>%s</comment>', $str), $options);
    }

    public function writeQuestion(string $str = '', int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<question>%s</question>', $str), $options);
    }

    public function writeError(string $str = '', int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->write(sprintf('<error>%s</error>', $str), $options);
    }

    public function write(string $str = '', int $options = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->output?->writeln($str, $options);
    }

}
