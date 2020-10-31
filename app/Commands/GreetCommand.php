<?php

namespace App\Commands;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;


class GreetCommand extends Command
{
    protected $commandName = 'app:greet';
    protected $commandDescription = "Greets Someone";

    protected $commandArgumentName = "name";
    protected $commandArgumentDescription = "Who do you want to greet?";

    protected $commandOptionName = "cap"; // should be specified like "app:greet John --cap"
    protected $commandOptionDescription = 'If set, it will greet in uppercase letters';

    /**
     * @var SymfonyStyle
     */
    protected $io;

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName($this->commandName)
            ->setDescription($this->commandDescription)
            ->addArgument(
                $this->commandArgumentName,
                InputArgument::OPTIONAL,
                $this->commandArgumentDescription
            )
            ->addOption(
                $this->commandOptionName,
                null,
                InputOption::VALUE_NONE,
                $this->commandOptionDescription
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
        $this->io->title($this->commandDescription);
        if ($input->getArgument('name') !== null) return true;

        $this->io->section('Interactive Wizard');
        $this->io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' $ php bin/console app:add-user username password email@example.com',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
        ]);

        // Ask for the username if it's not defined
        $name = $input->getArgument('name');
        if (null !== $name) {
            $this->io->text(' > <info>Name</info>: ' . $name);
        } else {
            $name = $this->io->ask('Name', 'anonimo');
            $input->setArgument('name', $name);
        }


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {



        $value = $this->io->askQuestion(new Question("asdasad", "boh"));
        $this->io->warning($value);
        try {


            $name = $input->getArgument($this->commandArgumentName);

            if ($name) {
                $text = 'Hello ' . $name;
            } else {
                $text = 'Hello';
            }

            if ($input->getOption($this->commandOptionName)) {
                $text = strtoupper($text);
            }

            $this->io->info("" . $text);

            $this->io->success("ole");

            return Command::SUCCESS;

        } catch (Exception $exception) {
            $this->io->error($exception->getMessage());
            return Command::FAILURE;
        }

    }
}