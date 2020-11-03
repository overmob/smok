<?php

namespace App\Commands;

use Exception;
use Noodlehaus\Config;
use stdClass;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
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
class Tunnel extends Command
{
    protected $commandName = 'tunnel';
    protected $commandDescription = "Manage Autossh revese tunnelling";

    protected $commandArgumentCmd = "cmd";
    protected $commandCmdDescription = "Command to execute [start, stop, status, restart]";


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
                $this->commandArgumentCmd,
                InputArgument::REQUIRED,
                $this->commandCmdDescription
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {
            switch ($input->getArgument('cmd')) {
                case "start":
                    if ($this->doStart()) $this->io->success("AutoSSH started with PID: " . $this->getPid());;
                    break;
                case "stop":
                    if ($this->doStop()) $this->io->success("AutoSSH stopped.");
                    break;
                case "restart":
                    if ($this->doStop() && $this->doStart())
                        $this->io->success("AutoSSH restarted.");;
                    break;
                case "status":
                    if ($this->getPid()>0)
                        $this->io->success("AutoSSH is running.");
                    else
                        $this->io->warning("AutoSSH is not running.");
                    break;
                default:
                    $help = new HelpCommand();
                    $help->setCommand($this);

                    return $help->run($input, $output);

            }


            return Command::SUCCESS;

        } catch (Exception $exception) {
            $this->io->error($exception->getMessage());
            return Command::FAILURE;
        }

    }

    private function getPid()
    {
        $pid = trim(shell_exec("pidof -x autossh 2>&1"));
        return $pid;
    }

    private function doStart()
    {
        $cmd = "autossh -q -M 0 -f -N -o \"ServerAliveInterval 60\" -o \"ServerAliveCountMax 3\" -i /home/pi/.ssh/id_rsa -R 2222:localhost:22 root@165.22.70.59";

        if (!$this->getPid() > 0) {
            $this->io->write('Starting AutoSSH... ');
            shell_exec($cmd);
            $this->io->writeln('Started!');
            return $this->getPid() > 0;
        } else {
            $this->io->warning('AutoSSH already started PID: ' . $this->getPid());
        }
    }

    private function doStop()
    {
        $cmd = "killall autossh";

        if ($this->getPid() > 0) {
            $this->io->write('Stopping AutoSSH... ');
            shell_exec($cmd);
            $this->io->writeln('Stopped!');
            return true;

        } else {
            $this->io->warning('AutoSSH is not running.');
        }
    }
}