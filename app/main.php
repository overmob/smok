<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use App\Commands\GreetCommand;

$application = new Application();

# add our commands
$application->add(new GreetCommand());

$application->run();