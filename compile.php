<?php

$appFile = 'bin/smok';

// clean up
if (file_exists($appFile))
{
    unlink($appFile);
}

if (file_exists($appFile . '.gz'))
{
    unlink($appFile . '.gz');
}

// create phar
$phar = new Phar($appFile.'.phar');

// start buffering. Mandatory to modify stub to add shebang
$phar->startBuffering();

// Create the default stub from main.php entrypoint
$defaultStub = $phar->createDefaultStub('app/main.php');

// Add the rest of the apps files
$include = '/^(?=(.*app|.*vendor))(.*)$/i';
$phar->buildFromDirectory(__DIR__ , $include);

// Customize the stub to add the shebang
$stub = "#!/usr/bin/env php \n" . $defaultStub;

// Add the stub
$phar->setStub($stub);

$phar->stopBuffering();

// plus - compressing it into gzip
$phar->compressFiles(Phar::GZ);

rename(__DIR__ . '/bin/smok.phar',__DIR__ . '/bin/smok');
# Make the file executable
chmod(__DIR__ . '/bin/smok', 0770);

echo "$appFile successfully created" . PHP_EOL;