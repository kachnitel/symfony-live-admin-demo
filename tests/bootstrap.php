<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Setup test database
if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'test') {
    passthru(sprintf(
        'php "%s/bin/console" doctrine:schema:drop --force --quiet --env=test',
        dirname(__DIR__)
    ));
    passthru(sprintf(
        'php "%s/bin/console" doctrine:schema:create --quiet --env=test',
        dirname(__DIR__)
    ));
}
