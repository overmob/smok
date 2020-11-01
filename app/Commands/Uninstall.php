<?php

namespace App\Commands;

use Exception;
use Noodlehaus\Config;
use stdClass;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;


/**
 * @property Config settings
 * @property SymfonyStyle io
 */
class Uninstall extends Command
{
    protected $commandName = 'uninstall';
    protected $commandDescription = "Uninstall the application and clear config .smok conf dir and others";


    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName($this->commandName)
            ->setDescription($this->commandDescription);

    }

    /**
     * This optional method is the first one executed for a command after configure()
     * and is useful to initialize properties based on the input arguments and options.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // SymfonyStyle is an optional feature that Symfony provides so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);

    }

    /**
     * This method is executed after initialize() and before execute(). Its purpose
     * is to check if some of the options/arguments are missing and interactively
     * ask the user for those values.
     *
     * This method is completely optional. If you are developing an internal console
     * command, you probably should not implement this method because it requires
     * quite a lot of work. However, if the command is meant to be used by external
     * users, this method is a nice way to fall back and prevent errors.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->io->title($this->commandName);
        $this->io->text($this->commandDescription);
        return true;


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $smokDir = conf('app.conf_path', '.smok');
            $tmpZip = conf('app.tmp_payload_zip', '.tmp/payload.zip');
            $tmpFolder = conf('app.tmp_payload_folder', '.tmp/payload');

            $filename = $smokDir . '/registration.json';
            if (file_exists($filename) && $this->io->confirm("Delete $filename?", true)) {
                $this->io->write('Deleting ' . $filename . ' ... ');
                unlink($filename);
                $this->io->writeln('OK!');
            }


            $filename = $smokDir . '/device.json';
            if (file_exists($filename) && $this->io->confirm("Delete $filename?", true)) {
                $this->io->write('Deleting ' . $filename . ' ... ');
                unlink($filename);
                $this->io->writeln('OK!');
            }

            if ((file_exists($tmpZip) || file_exists($tmpFolder)) && $this->io->confirm("Delete tmp files?", true)) {
                $this->io->write('Deleting ' . $tmpZip . ' ... ');
                if (file_exists($tmpZip)) unlink($tmpZip);
                $this->io->writeln('OK!');

                $this->io->write('Deleting ' . $tmpFolder . ' ... ');
                if (file_exists($tmpFolder)) rrmdir($tmpFolder);
                $this->io->writeln('OK!');
            }

            $this->io->success('Unistall succesfull');
            return Command::SUCCESS;

        } catch (Exception $exception) {
            $this->io->error($exception->getMessage());
            return Command::FAILURE;
        }

    }
}