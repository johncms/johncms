<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\Services;

use Johncms\Database\TransactionCallbacks;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\Services\CollectionCacheInvalidator;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCache;
use Johncms\Modules\Collections\Application\Services\CollectionStructureMemo;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\System\i18n\Translator;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryCache;

final class CollectionStructureMemoTest extends TestCase
{
    public function testAValueIsReadOnceAndSharedWithTheNextRequest(): void
    {
        $cache = InMemoryCache::create();
        $memo = new CollectionStructureMemo($cache, new Translator());
        $reads = 0;
        $load = static function () use (&$reads): string {
            $reads++;
            return 'value';
        };

        $memo->remember('fields_1', 1, $load);
        $memo->remember('fields_1', 1, $load);
        self::assertSame(1, $reads);
        $memo->remember('fields_2', 2, $load);
        self::assertSame(2, $reads, 'Every key is read on its own.');
        $memo->reset();
        $memo->remember('fields_1', 1, $load);
        self::assertSame(2, $reads, 'The next request reads it from the cache.');
        (new CollectionStructureMemo($cache, new Translator()))->remember('fields_1', 1, $load);
        self::assertSame(2, $reads, 'Every visitor is served the same entry.');
    }

    /** Names of collections and fields are translated as they are read. */
    public function testEveryLocaleHasItsOwnEntry(): void
    {
        $translator = new Translator();
        $memo = new CollectionStructureMemo(InMemoryCache::create(), $translator);
        $translator->setLocale('en');
        $memo->remember('fields_1', 1, static fn (): string => 'Title');
        $translator->setLocale('ru');

        self::assertSame('Заголовок', $memo->remember('fields_1', 1, static fn (): string => 'Заголовок'));
    }

    public function testATransactionAlwaysReadsWhatIsStored(): void
    {
        $memo = new CollectionStructureMemo(InMemoryCache::create(), new Translator());
        $memo->remember('fields_1', 1, static fn (): string => 'before');
        $pdo = new PDO('sqlite::memory:');
        $pdo->beginTransaction();
        TransactionCallbacks::begin($pdo);
        try {
            self::assertSame('stored', $memo->remember('fields_1', 1, static fn (): string => 'stored'));
        } finally {
            TransactionCallbacks::rollbackTo($pdo, 0);
            $pdo->rollBack();
        }
        self::assertSame('before', $memo->remember('fields_1', 1, static fn (): string => 'stored'), 'Outside the transaction the remembered value is back.');
    }

    public function testACommittedChangeForgetsOnlyItsCollection(): void
    {
        $cache = InMemoryCache::create();
        $memo = new CollectionStructureMemo($cache, new Translator());
        $invalidator = new CollectionCacheInvalidator($cache, new CollectionCodeCache($cache, new ContentCollectionRepository()), $memo);
        $memo->remember('fields_1', 1, static fn (): string => 'before');
        $memo->remember('fields_2', 2, static fn (): string => 'other');
        $memo->remember('declared_example/blog_articles', null, static fn (): string => 'lookup');

        $invalidator->afterCommit(new CollectionChangeDTO('field', 'saved', 1, 5, null, null));

        self::assertSame('after', $memo->remember('fields_1', 1, static fn (): string => 'after'));
        self::assertSame('other', $memo->remember('fields_2', 2, static fn (): string => 'changed'));
        self::assertSame('lookup', $memo->remember('declared_example/blog_articles', null, static fn (): string => 'changed'));
    }

    /** A collection is looked up by its declaration before its id is known. */
    public function testAChangeOfACollectionForgetsTheLookups(): void
    {
        $cache = InMemoryCache::create();
        $memo = new CollectionStructureMemo($cache, new Translator());
        $invalidator = new CollectionCacheInvalidator($cache, new CollectionCodeCache($cache, new ContentCollectionRepository()), $memo);
        $memo->remember('declared_example/blog_articles', null, static fn (): ?string => null);

        $invalidator->afterCommit(new CollectionChangeDTO('collection', 'saved', 1, 1, null, null));

        self::assertSame('found', $memo->remember('declared_example/blog_articles', null, static fn (): string => 'found'));
    }

    /** For a write that goes past the change handlers, such as the synchronization of declarations. */
    public function testTheWholeStructureCanBeForgotten(): void
    {
        $cache = InMemoryCache::create();
        $memo = new CollectionStructureMemo($cache, new Translator());
        $memo->remember('fields_1', 1, static fn (): string => 'before');
        $memo->remember('declared_example/blog_articles', null, static fn (): string => 'before');

        $memo->invalidate();

        $fresh = new CollectionStructureMemo($cache, new Translator());
        self::assertSame('after', $fresh->remember('fields_1', 1, static fn (): string => 'after'));
        self::assertSame('after', $fresh->remember('declared_example/blog_articles', null, static fn (): string => 'after'));
    }
}
