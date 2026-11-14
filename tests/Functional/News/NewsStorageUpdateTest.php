<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;
use Johncms\Database\ConnectionInterface;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Modules\Collections\Application\Api\CollectionLookupApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsImportApiInterface;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationsSynchronizer;
use Johncms\Modules\News\Application\Collections\NewsLegacyFileCleanup;
use Johncms\Modules\News\Application\Collections\NewsLegacyImporter;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Modules\News\Application\Collections\NewsStorageUpdater;
use Johncms\Modules\News\Application\DTO\NewsQueryDTO;
use Johncms\Modules\News\Application\Exceptions\NewsLegacyImportException;
use Johncms\Modules\News\Domain\Repository\NewsLegacyStorageRepositoryInterface;
use Johncms\Modules\News\Install\Installer;
use Johncms\Updates\SystemOperationContext;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;

/** DDL must run outside the transactional HTTP harness, in an isolated process and database. */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class NewsStorageUpdateTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        $database = getenv('TEST_DATABASE_URL');
        if (is_string($database) && $database !== '') {
            $parts = parse_url($database);
            $name = isset($parts['path']) ? trim($parts['path'], '/') : '';
            if (! str_ends_with($name, '_test')) {
                throw new RuntimeException('News update tests require a disposable database ending in _test.');
            }
            putenv('TEST_DATABASE_URL=' . substr($database, 0, strrpos($database, '/') + 1) . 'johncms_news_update_test');
        }
        parent::setUp();
        Capsule::connection()->rollBack();
        $this->container()->get(CollectionDeclarationsSynchronizer::class)->synchronize();
    }

    protected function tearDown(): void
    {
        Capsule::connection()->beginTransaction();
        parent::tearDown();
    }

    public function testFreshInstallationRemovesLegacySchemaAndCanCreateNewDiscussions(): void
    {
        $this->update();
        $this->assertLegacyRemoved();
        self::assertSame(0, Capsule::table('collection_items')->count());
        (new Installer('news'))->installDemoData();
        self::assertSame(4, Capsule::table('collection_items')->count());
        self::assertSame(4, $this->comments()->count());
        self::assertSame(4, Capsule::table('news_votes')->whereNotNull('item_id')->count());
        $this->update();
        (new Installer('news'))->installDemoData();
        self::assertSame(4, Capsule::table('collection_items')->count());
    }

    public function testRealUpdateKeepsDiscussionsAndPublicUrlsAndRemovesExcludedRows(): void
    {
        $section = Fixture::insert('news_sections', ['code' => 'Original', 'name' => 'Original']);
        $article = $this->article('Release.V10+', $section);
        $removed = $this->article('Removed', $section, ['deleted_at' => '2001-01-01 00:00:00']);
        $this->comment($article);
        $this->comment($removed);
        Fixture::insert('news_votes', ['article_id' => $article, 'user_id' => 10, 'vote' => 1]);
        Fixture::insert('news_votes', ['article_id' => $removed, 'user_id' => 10, 'vote' => 1]);
        $this->update();
        $this->assertLegacyRemoved();
        self::assertSame(1, Capsule::table('collection_items')->count());
        $news = $this->container()->get(NewsReadServiceInterface::class);
        $item = $news->findByCode('Original', 'Release.V10+');
        self::assertNotNull($item);
        self::assertSame('/news/Original/Release.V10+.html', $item->url);
        self::assertSame([$item->record->id], $this->comments()->pluck('subject_id')->map(intval(...))->all());
        self::assertSame(['Comment'], $this->comments()->pluck('text')->all());
        self::assertSame(1, $item->stats->comments);
        self::assertSame(1, Capsule::table('news_votes')->count());
        self::assertSame(1, $item->stats->rating);
        self::assertSame(1, $news->count(new NewsQueryDTO(search: 'Original body')));
        self::assertSame(200, $this->handleRequest($item->url)->getStatusCode());
        $this->update();
        self::assertSame(1, Capsule::table('collection_items')->count());
    }

    public function testDataFailureLeavesLegacyRowsAndMappingsIntactAndRetryImportsEverything(): void
    {
        $section = Fixture::insert('news_sections', ['code' => 'Original', 'name' => 'Original']);
        $first = $this->article('First', $section);
        $comment = $this->comment($first);
        $bad = $this->article('not a code', $section);
        try {
            $this->update();
            self::fail('The complete data phase must be rolled back.');
        } catch (NewsLegacyImportException $error) {
            self::assertStringContainsString('article ' . $bad, $error->getMessage());
            self::assertSame(2, Capsule::table('news_articles')->count());
            self::assertSame(1, Capsule::table('news_sections')->count());
            self::assertNull(Capsule::table('news_comments')->where('id', $comment)->value('item_id'));
            self::assertSame(0, Capsule::table('collection_items')->count());
            self::assertSame(0, Capsule::table('collection_sections')->count());
        }
        Capsule::table('news_articles')->where('id', $bad)->update(['code' => 'Second']);
        $this->update();
        $this->assertLegacyRemoved();
        self::assertSame(2, Capsule::table('collection_items')->count());
        self::assertSame(1, $this->comments()->count(), 'The comment of the article is carried into the core on the retry.');
    }

    public function testCleanupFailureKeepsCommittedMappingsAndDurableFileQueueForRetry(): void
    {
        $article = $this->article('Keep', Fixture::insert('news_sections', ['code' => 'section', 'name' => 'Section']));
        $this->article('Exclude', 0, ['deleted_at' => '2001-01-01 00:00:00', 'attached_files' => '[999999]']);
        $this->comment($article);
        $inner = $this->container()->get(NewsLegacyStorageRepositoryInterface::class);
        $storage = new class ($inner) implements NewsLegacyStorageRepositoryInterface {
            private bool $fail = true;
            public function __construct(private NewsLegacyStorageRepositoryInterface $inner) {}
            public function prepare(): void { $this->inner->prepare(); }
            public function hasData(): bool { return $this->inner->hasData(); }
            public function clearLegacyRows(): void { $this->inner->clearLegacyRows(); }
            public function moveDiscussionsToCore(): void { $this->inner->moveDiscussionsToCore(); }
            public function removeOrphanDiscussions(): void { $this->inner->removeOrphanDiscussions(); }
            public function isHeldByComment(int $fileId): bool { return $this->inner->isHeldByComment($fileId); }
            public function pendingFiles(): array {
                if ($this->fail) {
                    $this->fail = false;
                    throw new RuntimeException('Interrupted cleanup');
                }
                return $this->inner->pendingFiles();
            }
            public function acknowledgeFile(int $id): void { $this->inner->acknowledgeFile($id); }
            public function dropLegacySchema(): void { $this->inner->dropLegacySchema(); }
            public function hasLegacySchema(): bool { return $this->inner->hasLegacySchema(); }
        };
        $updater = new NewsStorageUpdater(
            $this->container()->get(NewsLegacyImporter::class), $storage,
            $this->container()->get(ConnectionInterface::class),
            $this->container()->get(NewsLegacyFileCleanup::class),
            $this->container()->get(CollectionLookupApiInterface::class),
            $this->container()->get(CollectionsImportApiInterface::class),
            $this->container()->get(SystemOperationContext::class), $this->container()->get(LoggerInterface::class)
        );
        try {
            $this->container()->get(SystemOperationContext::class)->runTrusted($updater->update(...));
            self::fail('The injected cleanup failure must be visible.');
        } catch (RuntimeException $exception) {
            self::assertSame('Interrupted cleanup', $exception->getMessage());
        }
        self::assertSame(0, Capsule::table('news_articles')->count());
        self::assertSame(1, Capsule::table('collection_items')->count());
        self::assertSame(1, Capsule::table('news_legacy_cleanup_files')->count());
        self::assertSame(1, $this->comments()->count(), 'The comments are carried before the files are cleaned up.');
        $this->container()->get(SystemOperationContext::class)->runTrusted($updater->update(...));
        $this->assertLegacyRemoved();
        self::assertSame(1, Capsule::table('collection_items')->count());
        self::assertSame(0, Capsule::table('news_legacy_cleanup_files')->count());
        self::assertSame(1, $this->comments()->count());
    }

    private function update(): void
    {
        $this->container()->get(SystemOperationContext::class)->runTrusted(fn () => $this->container()->get(NewsStorageUpdater::class)->update());
    }

    private function assertLegacyRemoved(): void
    {
        $schema = $this->container()->get(SchemaInterface::class);
        foreach (['news_articles', 'news_sections', 'news_search_index'] as $table) {
            self::assertFalse($schema->hasTable($table));
        }
        self::assertFalse($schema->hasTable('news_comments'));
        self::assertFalse($schema->hasColumn('news_votes', 'article_id'));
        self::assertFalse($schema->hasForeignKeyForColumns('news_votes', ['item_id']));
    }

    /** @param array<string, mixed> $attributes */
    private function article(string $code, int $section, array $attributes = []): int
    {
        return Fixture::insert('news_articles', $attributes + ['name' => $code, 'code' => $code, 'section_id' => $section, 'active' => true, 'text' => 'Original body', 'created_at' => '2001-01-01 00:00:00']);
    }

    private function comments(): Builder
    {
        return Capsule::table('comments')->where('subject_type', 'news');
    }

    private function comment(int $article): int
    {
        return Fixture::insert('news_comments', ['article_id' => $article, 'text' => 'Comment', 'created_at' => '2001-01-01 00:00:00']);
    }
}
