<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\Services;

use Johncms\Database\TransactionCallbacks;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\Services\CollectionCacheInvalidator;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCache;
use Johncms\Modules\Collections\Application\Services\CollectionStructureMemo;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryCache;

final class CollectionStructureMemoTest extends TestCase
{
    public function testAValueIsReadOnceUntilTheMemoIsReset(): void
    {
        $memo = new CollectionStructureMemo();
        $reads = 0;
        $load = static function () use (&$reads): string {
            $reads++;
            return 'value';
        };

        $memo->remember('fields_1', $load);
        $memo->remember('fields_1', $load);
        self::assertSame(1, $reads);
        $memo->remember('fields_2', $load);
        self::assertSame(2, $reads, 'Every key is read on its own.');
        $memo->reset();
        $memo->remember('fields_1', $load);
        self::assertSame(3, $reads);
    }

    public function testATransactionAlwaysReadsWhatIsStored(): void
    {
        $memo = new CollectionStructureMemo();
        $memo->remember('fields_1', static fn (): string => 'before');
        $pdo = new PDO('sqlite::memory:');
        $pdo->beginTransaction();
        TransactionCallbacks::begin($pdo);
        try {
            self::assertSame('stored', $memo->remember('fields_1', static fn (): string => 'stored'));
        } finally {
            TransactionCallbacks::rollbackTo($pdo, 0);
            $pdo->rollBack();
        }
        self::assertSame('before', $memo->remember('fields_1', static fn (): string => 'stored'), 'Outside the transaction the remembered value is back.');
    }

    public function testACommittedChangeOfACollectionIsForgotten(): void
    {
        $cache = InMemoryCache::create();
        $memo = new CollectionStructureMemo();
        $invalidator = new CollectionCacheInvalidator($cache, new CollectionCodeCache($cache, new ContentCollectionRepository()), $memo);
        $memo->remember('fields_1', static fn (): string => 'before');

        $invalidator->afterCommit(new CollectionChangeDTO('field', 'saved', 1, 5, null, null));

        self::assertSame('after', $memo->remember('fields_1', static fn (): string => 'after'));
    }
}
