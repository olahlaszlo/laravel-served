<?php

namespace Sinnbeck\LaravelServed\Shell;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Sinnbeck\LaravelServed\Exceptions\ShellCommandFailedException;

class Shell
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Shell constructor.
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param string $command
     * @param array $env
     */
    public function run(string $command, array $env = []): void
    {
        $process = Process::fromShellCommandline($command);
        if ($this->output->isVerbose()) {
            $this->writeCommand($process, $command, $env);
        }
        $process->setTimeout(null);

        $process->run($this->writeOutputBuffer(), $env);
    }

    /**
     * @param string $command
     * @param array $env
     * @return string
     */
    public function exec(string $command, array $env = []): string
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(null);
        if ($this->output->isVerbose()) {
            $this->writeCommand($process, $command, $env);
        }
        $callback = null;
        if ($this->output->isVeryVerbose()) {
            $callback = $this->writeOutputBuffer();
        }
        $process->run($callback, $env);

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    protected function writeCommand(Process  $process, $command, $env = [])
    {
        $this->output->writeln($process->createCommand($command, $env));
    }

    protected function writeOutputBuffer() {
        return function ($type, $buffer) {
            if (Process::ERR === $type && strpos(trim($buffer), 'WARNING: No blkio') === false) {
                throw new ShellCommandFailedException('ERROR : : ' . trim($buffer));
//                $this->output->writeln('ERROR : ' . $buffer);
            } else {
                $this->output->writeln($buffer);
            }
        };
    }
}
