<?php

declare(strict_types=1);

use Johncms\Mail\EmailSender;

define('CONSOLE_MODE', true);

require 'bootstrap.php';

$container = Johncms\System\Container\Factory::getContainer();
(new \Johncms\Logs\GlobalErrorHandler($container->get(\Psr\Log\LoggerInterface::class)))->registerHandlers();

EmailSender::send();
