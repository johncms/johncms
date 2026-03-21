<?php

declare(strict_types=1);

use Johncms\Mail\EmailSender;
use Johncms\Modules\Forum\Application\UseCases\CleanupOrphanForumFilesUseCase;

define('CONSOLE_MODE', true);

require 'bootstrap.php';

$container = \Johncms\Container\PSRContainerFactory::getContainer();
$logger = $container->get(\Psr\Log\LoggerInterface::class);
(new \Johncms\Logs\GlobalErrorHandler(
    logger:    $logger,
    container: $container
))->registerHandlers();

EmailSender::send();

if ((int) date('i') === 0) {
    $container->get(CleanupOrphanForumFilesUseCase::class)->execute();
}
