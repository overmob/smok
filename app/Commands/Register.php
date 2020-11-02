<?php

namespace App\Commands;

use Exception;
use Noodlehaus\Config;
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
class Register extends Command
{
    protected $commandName = 'register';
    protected $commandDescription = "Register a device on the server";

    protected $commandArgumentMac = "mac";
    protected $commandArgumentMacDescription = "Optional MAC address of device";

    protected $commandOptionName = "cap"; // should be specified like "app:greet John --cap"
    protected $commandOptionDescription = 'If set, it will greet in uppercase letters';


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
                $this->commandArgumentMac,
                InputArgument::OPTIONAL,
                $this->commandArgumentMacDescription
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
     * @throws Exception
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
        if ($input->getArgument('mac') !== null) return true;

        $this->io->section('Interactive Wizard');
        $this->io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' $ php bin/console app:add-user username password email@example.com',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
        ]);


        $deviceMac = $this->settings->get('mac');

        // Ask for the username if it's not defined
        $mac = $input->getArgument('mac');
        if (null !== $mac) {
            $this->io->text(' > <info>Mac address</info>: ' . $mac);
        } else {
            $mac = $this->io->ask('Mac address', trim($deviceMac));
            $input->setArgument('mac', $mac);
        }


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {

            $mac = $input->getArgument($this->commandArgumentMac);


            /*if ($input->getOption($this->commandOptionName)) {
                $text = strtoupper($mac);
            }*/

            $this->io->write("Registazione del device MAC: " . $mac . '... ');
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => conf('app.registration_url'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => array('mac' => $mac),
                CURLOPT_HEADER => true,
            ));

            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $body = substr($response, $header_size);
            curl_close($curl);

            if ($httpcode !== 200) {
                $this->io->error("Server response error HTTP: " . $httpcode );
                return Command::FAILURE;
            }else
            {
                $this->io->writeln("OK");
                $this->io->write("Saving registration.json file...");
                $registrationSettings = json_decode($body);
                file_put_contents(conf('app.conf_path', '.smok') . '/registration.json', json_encode($registrationSettings, JSON_PRETTY_PRINT));
                $this->io->write("OK");

                $this->settings = new Config(conf('app.conf_path', '.smok'));
                $this->io->success("Device succesfully registered\n" . json_encode($registrationSettings, JSON_PRETTY_PRINT));
            }

            return Command::SUCCESS;

        } catch (Exception $exception) {
            $this->io->error($exception->getMessage());
            return Command::FAILURE;
        }


    }
}