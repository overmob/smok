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
class Install extends Command
{
    protected $commandName = 'install';
    protected $commandDescription = "Install the application and setup .smok conf dir";


    protected $commandOptionForce = "force";
    protected $commandOptionForceDescription = 'If set, it will reset .smok dir and clear all configs';



    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName($this->commandName)
            ->setDescription($this->commandDescription)
            ->addOption(
                $this->commandOptionForce,
                null,
                InputOption::VALUE_NONE,
                $this->commandOptionForceDescription
            );
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
        if ($input->getOption('force')) {
            $input->setOption('force', $this->io->confirm("Are you shure you want to reset all configs in .smok dir?", true));

        }

        return true;


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $smokDir = conf('app.conf_path', '.smok');
        if (!file_exists($smokDir)) mkdir($smokDir, 0755, true);

        try {
            if ($input->getOption('force')) {
                $this->io->write('Clearing ' . $smokDir . ' dir... ');
                array_map('unlink', array_filter((array)glob("$smokDir/*")));
                $this->io->writeln('Cleared!');
            }

            $deviceFile = $smokDir . '/device.json';
            if (!file_exists($deviceFile)) {

                $if = conf('app.main_eth');
                $this->io->write('Finding MAC for interface \'' . $if.'\'... ' );
                $deviceMac = trim(shell_exec("ifconfig $if | grep -o -E '([[:xdigit:]]{1,2}:){5}[[:xdigit:]]{1,2}'"));
                if($deviceMac=='') throw new Exception("Can't find MAC address for if ".$if);
                else $this->io->writeln('OK! '.$deviceMac );

                $this->io->write('Creating ' . $deviceFile.'... ' );
                $data = new StdClass();
                $data->mac = $deviceMac;
                $data->first_install = (new \DateTimeImmutable())->format('c');
                file_put_contents($deviceFile, json_encode($data, JSON_PRETTY_PRINT));
                $this->io->writeln('Created '.json_encode($data));
                $this->settings = new Config(conf('app.conf_path', '.smok'));
                $this->io->success("Succesfully installed");

            }else
            {
                $this->io->writeln("$deviceFile already present.");
                $this->io->warning("Already installed. Use --force option to force full reinstall.");
            }


            return Command::SUCCESS;

        } catch (Exception $exception) {
            $this->io->error($exception->getMessage());
            return Command::FAILURE;
        }

    }
}