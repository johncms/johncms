<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Johncms\Cache;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('cache:clear', 'Clear cache')]
class ClearCacheCommand extends Command
{
    protected function configure(): void
    {
        $this->setHelp('This command allows you to clear the cache.');
    }

    /**
     * @psalm-suppress PossiblyInvalidArgument
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $cache = di(Cache::class);
        $cache->clear();

        $style->success('The cache was successfully cleared');

        return Command::SUCCESS;
    }
}
