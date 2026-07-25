<?php

declare(strict_types=1);

use Johncms\Container\PSRContainerFactory;
use Johncms\Http\Kernel;
use Johncms\Http\Request;
use Johncms\Mail\EmailSender;

// Paths are resolved from __DIR__: both lines run before the constants are defined.
// If the system is not installed, redirect to the installer.
if (! is_file(dirname(__DIR__) . '/config/autoload/database.local.php')) {
    header('Location: /install/');
    exit;
}

require dirname(__DIR__) . '/system/bootstrap.php';

// The error handler is registered inside the bootstrap, so boot failures are covered too.
$container = PSRContainerFactory::getContainer();

$response = $container->get(Kernel::class)->handle($container->get(Request::class));

// send(false) leaves the output buffer of system/bootstrap.php and fastcgi_finish_request()
// alone. Both, together with the post-response work below, move into Kernel::terminate()
// in stage 3b.
$response->send(false);

// If cron usage is disabled. Only successfully served requests flush the queue: before the kernel
// existed, a redirect or a 404 exited before reaching this point, and an error page has no
// business running the mail queue. It moves into Kernel::terminate() in stage 3b anyway.
if (! USE_CRON && ! defined('_IN_JOHNADM') && $response->isSuccessful()) {
    $cron_cache = CACHE_PATH . 'cron.cache';
    if (! file_exists($cron_cache) || filemtime($cron_cache) < (time() - 5)) {
        EmailSender::send();
        file_put_contents($cron_cache, time());
    }
}
