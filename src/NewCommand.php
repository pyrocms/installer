<?php

namespace Pyro\Installer\Console;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class NewCommand extends Command
{

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new Pyro application.')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption('tag', null, InputOption::VALUE_OPTIONAL, 'Installs a specific version tag of Pyro.')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs Pyro with source preferred.');
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface   $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->verifyApplicationDoesntExist(
            $directory = ($input->getArgument('name')) ? getcwd() . '/' . $input->getArgument('name') : getcwd()
        );

        $options = '';

        if ($input->getOption('dev')) {
            $options .= ' --prefer-source';
        }

        if ($tag = $input->getOption('tag')) {
            $tag = '=' . $tag;
        }

        $output->writeln('<info>Installing Pyro...</info>');

        $composer = $this->findComposer();

        $commands = [
            $composer . ' create-project pyrocms/pyrocms' . $tag . ' ' . $directory . $options,
            $composer . ' install',
        ];

        if ($input->getOption('no-ansi')) {
            $commands = array_map(
                function ($value) {
                    return $value . ' --no-ansi';
                },
                $commands
            );
        }

        $process = new Process(implode(' && ', $commands), $directory, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(
            function ($type, $line) use ($output) {
                $output->write($line);
            }
        );

        $output->writeln('<comment>Pyro is ready!</comment>');
    }

    /**
     * Verify that the application does not already exist.
     *
     * @param  string $directory
     * @return void
     */
    protected function verifyApplicationDoesntExist($directory)
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd()) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd() . '/composer.phar')) {
            return '"' . PHP_BINARY . '" composer.phar';
        }

        return 'composer';
    }
}
