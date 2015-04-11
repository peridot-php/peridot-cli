<?php

/**
 * This file represents the command line interface exposed by Peridot.
 * It is meant to be included in an executable where additional action may be
 * taken on the returned Application instance.
 *
 * @return Peridot\Cli\Application
 */

$autoloaders = [
    __DIR__ . '/../../../autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../autoload.php'
];

foreach ($autoloaders as $file) {
    if (file_exists($file)) {
        define('PERIDOT_COMPOSER_INSTALL', $file);
        break;
    }
}

unset($autoloaders, $file);

if (!defined('PERIDOT_COMPOSER_INSTALL')) {
    fwrite(STDERR,
        'You need to set up the project dependencies using the following commands:' . PHP_EOL .
        'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
        'php composer.phar install' . PHP_EOL
    );
    exit(1);
}

require_once PERIDOT_COMPOSER_INSTALL;

use Evenement\EventEmitter;
use Peridot\Cli\Application;
use Peridot\Cli\CliOptionParser;
use Peridot\Cli\Environment;
use Peridot\Cli\InputDefinition;

$parser = new CliOptionParser(['-c', '--configuration'], $argv);
$environment = new Environment(
    new InputDefinition(),
    new EventEmitter(),
    $parser->parse()
);

return new Application($environment);
