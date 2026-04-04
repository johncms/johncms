<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\Scheduler\AsScheduledTask;
use Johncms\Sitemap\SitemapGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'sitemap:generate',
    description: 'Generate sitemap and update robots.txt',
)]
#[AsScheduledTask(expression: '0 3 * * *', withoutOverlapping: true)]
final class CronGenerateSitemapCommand extends Command
{
    public function __construct(
        private readonly SitemapGenerator $sitemapGenerator,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->sitemapGenerator->generate();
            $this->logger->info('Sitemap has been generated successfully.');
            $output->writeln('<info>Sitemap has been generated successfully.</info>');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->logger->error(
                'Sitemap generation failed.',
                ['exception' => $exception]
            );
            $output->writeln('<error>Sitemap generation failed.</error>');
            return self::FAILURE;
        }
    }
}
