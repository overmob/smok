<?php

require __DIR__ . '/../vendor/autoload.php';

use Noodlehaus\Config;
use Symfony\Component\Console\Application;


// Load all supported files in a directory
$env = getenv('APP_ENV');
if ($env == null) {
    $env = 'prod';
    putenv("APP_ENV=$env");
}

$conf = new Config(__DIR__ . '/../config/config.' . $env . '.yml');

$application = new Application();

# add our commands
$application->add(new \App\Commands\Test());
$application->add(new \App\Commands\Install());
$application->add(new \App\Commands\Uninstall());
$application->add(new \App\Commands\Register());
$application->add(new \App\Commands\UpdateWeb());
$application->add(new \App\Commands\Tunnel());
$application->run();


function conf($key, $default = null)
{
    global $conf;
    return $conf->get($key, $default);
}

function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                    rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                else
                    unlink($dir . DIRECTORY_SEPARATOR . $object);
            }
        }
        rmdir($dir);
    }
}