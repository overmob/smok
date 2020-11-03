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
class Test extends Command
{
    protected $commandName = 'test';
    protected $commandDescription = "Test for development";


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
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // SymfonyStyle is an optional feature that Symfony provides so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
        try {
            $this->settings = new Config(conf('app.conf_path', '.smok'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . '. Try \'smok install\' first.');
        }

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
        $this->io->newLine();

        return true;


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {

            $cmd = "autossh -q -M 0 -f -N -o \"ServerAliveInterval 60\" -o \"ServerAliveCountMax 3\" -i /home/pi/.ssh/id_rsa -R 2222:localhost:22 root@165.22.70.59";
            if (! shell_exec("pidof -x autossh") > 0) {
                $this->io->text(shell_exec($cmd));
            }


            return Command::SUCCESS;

        } catch (Exception $exception) {
            $this->io->error($exception->getMessage());
            return Command::FAILURE;
        }

    }
}