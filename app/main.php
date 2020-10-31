<?php

require __DIR__ . '/../vendor/autoload.php';

use Noodlehaus\Config;
use Symfony\Component\Console\Application;


// Load all supported files in a directory
$conf = new Config(__DIR__.'/../config/config.yml');

$application = new Application();

# add our commands
$application->add(new \App\Commands\GreetCommand());
$application->add(new \App\Commands\Install());
$application->add(new \App\Commands\Register());
$application->run();


function conf($key, $default = null)
{
    global $conf;
    return $conf->get($key, $default);
}
