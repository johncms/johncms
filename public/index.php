<?php

declare(strict_types=1);

use Johncms\Container\PSRContainerFactory;
use Johncms\Http\Kernel;
use Johncms\Http\Request;

// Paths are resolved from __DIR__: both lines run before the constants are defined.
// If the system is not installed, redirect to the installer.
if (! is_file(dirname(__DIR__) . '/config/autoload/database.local.php')) {
    header('Location: /install/');
    exit;
}

require dirname(__DIR__) . '/system/bootstrap.php';

// The error handler is registered inside the bootstrap, so boot failures are covered too.
$container = PSRContainerFactory::getContainer();
$kernel = $container->get(Kernel::class);
$request = $container->get(Request::class);

$response = $kernel->handle($request);
// The session is already closed by Kernel::handle() at this point, so send(true) can call
// fastcgi_finish_request() and flush the response to the client before terminate() below runs
// its post-response work (UserStat, the mail queue).
$response->send();
$kernel->terminate($request, $response);
