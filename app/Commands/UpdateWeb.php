<?php

namespace App\Commands;

use Exception;
use http\Client;
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
class UpdateWeb extends Command
{
    protected $commandName = 'update:web';
    protected $commandDescription = 'Update app content, from the web.';

    protected $commandOptionForce = "force";
    protected $commandOptionForceDescription = 'If set, it will force download from web';

    protected $commandOptionNodownload = "nodownload";
    protected $commandOptionNodownloadDescription = 'If set, will not download updates from remote. It unzip local payload';

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
                'f',
                InputOption::VALUE_NONE,
                $this->commandOptionForceDescription
            )->addOption(
                $this->commandOptionNodownload,
                null ,
                InputOption::VALUE_NONE,
                $this->commandOptionNodownloadDescription
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
        return true;


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $contentUrl = $this->settings->get('content_url', null);
            $contentVersion = $this->settings->get('content_version', null);
            if ($contentUrl == '') throw new Exception('No \'content_url\' for this device. Did you \'smok register\' before try update?');

            $tmpZipPayloadPath = conf('app.tmp_payload_zip', '/tmp/payload.zip');
            $tmpUncompressedPath = conf('app.tmp_payload_folder', '/tmp/payload/');

            $appInfoFile = conf('app.www_path', '/var/www/html') . '/info.json';

            rrmdir($tmpUncompressedPath);
            if (!file_exists($tmpUncompressedPath)) mkdir($tmpUncompressedPath, 0755, true);

            if (!$input->getOption('nodownload')) {

                $this->io->write('Requested version: ' . $contentVersion . ', ');
                /** @var Config $appInfo */
                $appInfo = null;
                if (file_exists($appInfoFile)) {
                    $appInfo = new Config($appInfoFile);
                    $this->io->write('actual version: ' . $appInfo->get('version') . '... ');
                }


                if ($appInfo != null && !$input->getOption('force') && $appInfo->get('version') == $contentVersion) {
                    $this->io->writeln('No need to update. ');
                    $this->io->success("App content already up to date.");
                    return Command::SUCCESS;
                } else {
                    $this->io->writeln('Update available. ');
                }


                $this->io->writeln("Downloading $contentUrl");
                $progress = $this->io->createProgressBar();
                $ctx = stream_context_create(array(), array('notification' => function ($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) use ($output, $progress) {

                    switch ($notification_code) {
                        case STREAM_NOTIFY_FILE_SIZE_IS:
                            $progress->setMaxSteps($bytes_max);
                            $progress->start();
                            break;
                        case STREAM_NOTIFY_PROGRESS:
                            $progress->setProgress($bytes_transferred);
                            break;

                    }
                }));
                $file = file_get_contents($contentUrl, false, $ctx);
                if ($progress) $progress->finish();
                $this->io->writeln(" Download complete");

                $this->io->write("Saving temp file... ");
                file_put_contents($tmpZipPayloadPath, $file);
                $this->io->writeln("OK");

            } else {
                $this->io->writeln('Running in --nodownload mode: skipping network');
            }


            $this->io->writeln("Extracting $tmpZipPayloadPath ");

            $progress = $this->io->createProgressBar();
            $progress->setMessage("Extracting");
            $zip = zip_open($tmpZipPayloadPath);
            $progress->setMaxSteps(filesize($tmpZipPayloadPath));
            if ($zip === null) throw new Exception("Cannot open zipfile: $tmpZipPayloadPath");
            $p = 0;
            while ($zip_entry = zip_read($zip)) {
                $fp = fopen($tmpUncompressedPath . zip_entry_name($zip_entry), "w");
                if (zip_entry_open($zip, $zip_entry, "r")) {
                    $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                    fwrite($fp, "$buf");
                    $p += zip_entry_compressedsize($zip_entry);
                    $progress->setProgress($p);
                    zip_entry_close($zip_entry);
                    fclose($fp);
                }
            }
            zip_close($zip);
            $progress->finish();
            $this->io->writeln(" Unzip complete");

            if (file_exists($tmpUncompressedPath . 'info.json')) {
                $wwwDir = conf('app.www_path');
                $this->io->write("Deploying ...");
                if ($this->io->confirm('Will you deploy the new app?', true)) {

                    if (file_exists($wwwDir . '_bak')) rrmdir($wwwDir . '_bak');
                    if (rename($wwwDir, $wwwDir . '_bak') && rename($tmpUncompressedPath, $wwwDir)) {
                        $this->io->writeln("OK!");
                        $this->io->success("Successfully updated!");
                        return Command::SUCCESS;
                    } else {
                        throw new Exception("Cannot move $tmpUncompressedPath to $wwwDir");
                    }

                } else {
                    $this->io->success("Update ready to deploy $tmpUncompressedPath ");
                }
                return Command::SUCCESS;
            } else {
                $this->io->error("Cannot find info.json in $tmpUncompressedPath");
                return Command::FAILURE;
            }


        } catch (Exception $exception) {
            $this->io->error($exception->getMessage());
            return Command::FAILURE;
        }


    }


}