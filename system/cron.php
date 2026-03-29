<?php

declare(strict_types=1);

use Johncms\Mail\EmailSender;
use Johncms\Modules\Forum\Application\UseCases\CleanupOrphanForumFilesUseCase;
use Johncms\Sitemap\SitemapGenerator;

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

if ((int) date('G') === 3 && (int) date('i') === 0) {
    try {
        $container->get(SitemapGenerator::class)->generate();
        $logger->info('Sitemap has been generated successfully.');
    } catch (Throwable $exception) {
        $logger->error(
            'Sitemap generation failed.',
            ['exception' => $exception]
        );
    }
}
