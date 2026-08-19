<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database\Migrations;

use Johncms\Cache\CacheInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * How many migrations this database has not been through yet, for the pages that want to say so.
 *
 * Cached, because the answer is the same for every visitor and every page of the admin panel
 * would otherwise pay for a directory listing and a query. The entry is dropped as soon as a run
 * finishes, so the notice goes away with the migration rather than with the expiry.
 */
final readonly class PendingMigrations
{
    public const string CACHE_TAG = 'migrations';

    private const string CACHE_KEY = 'migrations_pending_count';

    private const int CACHE_TTL = 60;

    public function __construct(
        private Migrator $migrator,
        private CacheInterface $cache,
        private LoggerInterface $logger,
    ) {
    }

    public function count(): int
    {
        return $this->cache->remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            fn (): int => $this->countPending(),
            [self::CACHE_TAG]
        );
    }

    /**
     * Called when a run finishes, so that the answer does not outlive what it describes.
     */
    public function forget(): void
    {
        $this->cache->invalidateTags(self::CACHE_TAG);
    }

    /**
     * A migration nobody can read — a file named like something else, two files claiming one
     * version — makes this unanswerable. It is logged and counted as none: this is a notice on a
     * page, and taking down every page of the admin panel with it would hide the problem behind a
     * worse one. What the file is wrong about is said by migrate and migrate:status.
     */
    private function countPending(): int
    {
        try {
            return count($this->migrator->pending());
        } catch (Throwable $exception) {
            $this->logger->error('Could not tell how many migrations are pending.', ['exception' => $exception]);

            return 0;
        }
    }
}
