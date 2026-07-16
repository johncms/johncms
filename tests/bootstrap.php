<?php

declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (! is_file($autoload)) {
    throw new RuntimeException('Composer autoload was not found. Run composer install inside php-fpm container.');
}

require $autoload;
