<?php

/**
 * Router of the built-in server of PHP for InstallerTest, doing what nginx of the stack does: a
 * file or a directory that exists under public/ is served as it is — the installer among them —
 * and every other address goes to the front controller.
 */

declare(strict_types=1);

$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path !== '/' && file_exists(dirname(__DIR__, 2) . '/public' . $path)) {
    return false;
}

require dirname(__DIR__, 2) . '/public/index.php';
