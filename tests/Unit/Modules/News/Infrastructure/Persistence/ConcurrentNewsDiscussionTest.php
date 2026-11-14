<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\News\Infrastructure\Persistence;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionItemRelationsApi;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Domain\Exceptions\CollectionWriteConflictException;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\Modules\News\Infrastructure\Persistence\Repository\EloquentNewsDiscussionRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\CollectionWriterDatabase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalDatabase;
use Tests\Support\RunsMigrations;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ConcurrentNewsDiscussionTest extends TestCase
{
    use RunsMigrations;

    private int $collection;
    private int $item;
    private CollectionItemRelationsApi $relations;
    private EloquentNewsDiscussionRepository $discussion;

    protected function setUp(): void
    {
        if (! FunctionalDatabase::fromEnvironment()->isMysql()) {
            self::markTestSkipped('Concurrent discussion writes require MySQL or MariaDB.');
        }
        $url = preg_replace('~/[^/]+$~', '/johncms_news_relations_test', (string) getenv(FunctionalDatabase::VARIABLE));
        putenv(FunctionalDatabase::VARIABLE . '=' . $url);
        FunctionalDatabase::fromEnvironment()->prepare();
        CollectionWriterDatabase::boot();
        $this->migrate('collections');
        $this->migrate('news');
        $this->collection = Fixture::insert('collections', ['code' => 'news', 'name' => 'News', 'active' => true]);
        $section = Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'code' => 'section', 'name' => 'Section', 'active' => true]);
        $this->item = CollectionItemWriterFactory::create()->execute(null, new CollectionItemFormDTO($this->collection, $section, 'entry', 'Entry', true, null, null, 100, null, null, []));
        $this->relations = new CollectionItemRelationsApi(
            new CollectionTransaction(),
            new ContentCollectionRepository(),
            new ContentCollectionItemRepository(new ContentCollectionFieldRepository(), new ContentCollectionItemQueryCompiler())
        );
        $this->discussion = new EloquentNewsDiscussionRepository();
    }

    #[DataProvider('hiddenStates')]
    public function testPublicationChecksUseCurrentReadsInsideAnOldSnapshot(string $mode): void
    {
        Capsule::connection()->beginTransaction();
        Capsule::table('collection_items')->count();
        [$process, $output] = $this->start($mode);
        self::assertSame(0, $this->finish($process, $output));
        try {
            $this->relations->withItemLock(collectionId: $this->collection, itemId: $this->item, operation: fn () => $this->discussion->vote($this->item, 11, 1), publishedOnly: true);
            self::fail('An old snapshot must not admit a vote after publication was revoked.');
        } catch (CollectionItemFormException | CollectionWriteConflictException) {
            self::assertSame(0, Capsule::table('news_votes')->count());
        } finally {
            Capsule::connection()->rollBack();
        }
    }

    public static function hiddenStates(): iterable
    {
        yield 'hidden item' => ['hide-item'];
        yield 'hidden ancestor' => ['hide-section'];
        yield 'hidden collection' => ['hide-collection'];
    }

    public function testVoteUpsertUsesCurrentRowsAndDoesNotDuplicateInsideAnOldSnapshot(): void
    {
        Capsule::connection()->beginTransaction();
        Capsule::table('news_votes')->count();
        [$process, $output] = $this->start('vote');
        self::assertSame(0, $this->finish($process, $output));
        try {
            $rating = $this->relations->withItemLock(collectionId: $this->collection, itemId: $this->item, operation: function (): int {
                $this->discussion->vote($this->item, 11, -1);
                return $this->discussion->stats(ids: [$this->item], userId: 11, current: true)[$this->item]->rating;
            }, publishedOnly: true);
            self::assertSame(-1, $rating);
            Capsule::connection()->commit();
        } catch (CollectionWriteConflictException) {
            self::assertTrue($this->snapshotIsolation());
            Capsule::connection()->rollBack();
        }
        self::assertSame(1, Capsule::table('news_votes')->count());
    }

    private function snapshotIsolation(): bool
    {
        $rows = Capsule::connection()->select("SHOW VARIABLES LIKE 'innodb_snapshot_isolation'");
        return $rows !== [] && in_array($rows[0]->Value, ['ON', '1', 1], true);
    }

    /** @return array{resource, resource} */
    private function start(string $mode): array
    {
        $output = tmpfile();
        self::assertIsResource($output);
        $process = proc_open([PHP_BINARY, ROOT_PATH . 'tests/Support/news-relations-writer.php', (string) $this->collection, (string) $this->item, $mode], [0 => ['pipe', 'r'], 1 => $output, 2 => $output], $pipes, ROOT_PATH);
        self::assertIsResource($process);
        fclose($pipes[0]);
        return [$process, $output];
    }

    /** @param resource $process
     * @param resource $output
     */
    private function finish($process, $output): int
    {
        $code = proc_close($process);
        rewind($output);
        $message = stream_get_contents($output);
        fclose($output);
        self::assertContains($code, [0, 2], $message === false ? '' : $message);
        return $code;
    }
}
