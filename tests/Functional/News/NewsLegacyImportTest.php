<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationsSynchronizer;
use Johncms\Modules\Collections\Application\Services\CollectionItemFiles;
use Johncms\Modules\News\Application\Collections\NewsLegacyImporter;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Modules\News\Application\Exceptions\NewsLegacyImportException;
use Johncms\Updates\SystemOperationContext;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;

final class NewsLegacyImportTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->container()->get(CollectionDeclarationsSynchronizer::class)->synchronize();
    }

    public function testImportsHierarchyHistoryMetadataFilesAndDiscussionsWithScopedCanonicalUrls(): void
    {
        $top = $this->section('Top');
        $child = $this->section('Child', $top);
        $removed = $this->section('Removed', deleted: true);
        $excludedChild = $this->section('InRemoved', $removed);
        $blank = $this->section(null, name: 'Blank section');
        $duplicate = $this->section('Top');
        $file = $this->file('news');
        $first = $this->article('Same.Code+', $child, ['attached_files' => json_encode([$file, 999999]), 'tags' => ' PHP ,php,"two, words"', 'view_count' => 42, 'created_by' => 77, 'updated_by' => 78, 'page_title' => 'SEO title', 'keywords' => str_repeat('seo,', 100)]);
        $second = $this->article('Same.Code+', $top);
        $root = $this->article('Root.Code+');
        $rootDuplicate = $this->article('Root.Code+');
        $reserved = $this->article('Root.Code+-legacy-' . $rootDuplicate);
        $this->article('Gone', $excludedChild, ['attached_files' => json_encode([$file])]);
        $this->article('Deleted', attributes: ['deleted_at' => '2020-01-01 00:00:00']);
        $comment = Fixture::insert('news_comments', ['created_at' => '2001-02-03 04:05:06', 'article_id' => $first, 'text' => 'Comment']);
        $deletedComment = Fixture::insert('news_comments', ['created_at' => '2001-02-03 04:05:06', 'article_id' => $first, 'deleted_at' => '2020-01-01 00:00:00']);
        $vote = Fixture::insert('news_votes', ['article_id' => $first, 'vote' => -1]);
        $report = $this->runImport();
        self::assertCount(4, $report->sections);
        self::assertCount(5, $report->articles);
        self::assertSame(2, $report->excludedSections);
        self::assertSame(2, $report->excludedArticles);
        self::assertSame('/news/Top/Child/Same.Code+.html', $report->urls[$first]);
        self::assertSame('/news/Top/Same.Code+.html', $report->urls[$second]);
        self::assertSame('/news/Root.Code+.html', $report->urls[$root]);
        self::assertSame('/news/Root.Code+-legacy-' . $rootDuplicate . '-1.html', $report->urls[$rootDuplicate]);
        self::assertSame('/news/Root.Code+-legacy-' . $rootDuplicate . '.html', $report->urls[$reserved]);
        self::assertStringContainsString('missing from the file registry', implode("\n", $report->warnings));
        self::assertStringContainsString("code '' changed to 'blank-section'", implode("\n", $report->warnings));
        self::assertStringContainsString("code 'Top' changed to 'Top-legacy-$duplicate'", implode("\n", $report->warnings));
        $api = $this->container()->get(CollectionsApiInterface::class);
        $item = $api->getItem($report->articles[$first]);
        self::assertSame(42, $item->viewCount);
        self::assertSame(77, $item->createdBy);
        self::assertSame(78, $item->updatedBy);
        self::assertSame('2001-02-03 04:05:06', $item->createdAt?->format('Y-m-d H:i:s'));
        self::assertNull($item->updatedAt);
        self::assertSame('Raw <b>body</b> & text', $item->detailText);
        self::assertSame(['php', 'two, words'], $item->values['tags']);
        self::assertSame(str_repeat('seo,', 100), $item->values['keywords']);
        $section = Capsule::table('collection_sections')->where('id', $report->sections[$blank])->first();
        self::assertNull($section->created_at);
        self::assertNull($section->updated_at);
        self::assertSame('Section <b>body</b>', $section->description);
        self::assertSame('section keywords', json_decode($section->metadata, true)['meta_keywords']);
        foreach ([$comment, $deletedComment] as $id) {
            self::assertSame($report->articles[$first], (int) Capsule::table('news_comments')->where('id', $id)->value('item_id'));
            self::assertSame($first, (int) Capsule::table('news_comments')->where('id', $id)->value('article_id'));
        }
        self::assertSame($report->articles[$first], (int) Capsule::table('news_votes')->where('id', $vote)->value('item_id'));
        self::assertSame([$file], $this->container()->get(CollectionItemFiles::class)->registeredFileIds($item->collectionId, $item->id));
        self::assertSame(1, Capsule::table('news_legacy_cleanup_files')->count());
        self::assertSame(0, Capsule::table('collection_search_documents')->count());
        $news = $this->container()->get(NewsReadServiceInterface::class);
        self::assertSame($item->id, $news->findByCode('Top/Child', 'Same.Code+')->record->id);
        self::assertSame($report->articles[$second], $news->findByCode('Top', 'Same.Code+')->record->id);
        self::assertSame(7, Capsule::table('news_articles')->count());
    }

    public function testFailureRollsBackImportedTreeItemsFileLinksAndDiscussionMappingsAndRetryWorks(): void
    {
        $section = $this->section('section');
        $file = $this->file('news');
        $first = $this->article('First', $section, ['attached_files' => json_encode([$file])]);
        $comment = Fixture::insert('news_comments', ['created_at' => '2001-02-03 04:05:06', 'article_id' => $first]);
        $bad = $this->article('Bad', $section, ['active_from' => '2000-01-01 00:00:00', 'active_to' => '1999-01-01 00:00:00']);
        try {
            $this->runImport();
            self::fail('Invalid publication interval must abort the entire import.');
        } catch (\Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException) {
            self::assertSame(0, Capsule::table('collection_items')->count());
            self::assertSame(0, Capsule::table('collection_sections')->count());
            self::assertSame(0, Capsule::table('collection_file_links')->count());
            self::assertNull(Capsule::table('news_comments')->where('id', $comment)->value('item_id'));
            self::assertSame(2, Capsule::table('news_articles')->count());
            self::assertSame(1, Capsule::table('files')->where('id', $file)->count());
        }
        Capsule::table('news_articles')->where('id', $bad)->update(['active_to' => null]);
        self::assertCount(2, $this->runImport()->articles);
    }

    public function testCallerRollbackAndExistingTargetConflictPreserveAllOriginalData(): void
    {
        $id = $this->article('Original');
        try {
            Capsule::connection()->transaction(function (): void {
                $this->runImport();
                throw new \RuntimeException('Rollback');
            });
        } catch (\RuntimeException $error) {
            self::assertSame('Rollback', $error->getMessage());
        }
        self::assertSame(0, Capsule::table('collection_items')->count());
        $report = $this->runImport();
        try {
            $this->runImport();
            self::fail('Existing collection records must never be overwritten or renamed by an import.');
        } catch (NewsLegacyImportException $error) {
            self::assertStringContainsString('conflicts with existing collection content', $error->getMessage());
        }
        self::assertSame(1, Capsule::table('collection_items')->count());
        self::assertSame('Original', $this->container()->get(CollectionsApiInterface::class)->getItem($report->articles[$id])->code);
    }

    public function testCorruptSectionTreeAbortsInsteadOfSilentlyDroppingArticles(): void
    {
        $this->section('orphan', 999999);
        $this->expectException(NewsLegacyImportException::class);
        $this->expectExceptionMessage('missing parent or a cycle');
        $this->runImport();
    }

    public function testSharedFilesRemainExternallyOwnedUntilTheLastCommentIsRemoved(): void
    {
        $file = $this->file('news');
        $article = $this->article('Shared', attributes: ['attached_files' => json_encode([$file])]);
        $comment = Fixture::insert('news_comments', ['created_at' => '2001-02-03 04:05:06', 'article_id' => $article, 'attached_files' => json_encode([$file])]);
        $report = $this->runImport();
        self::assertSame(0, (int) Capsule::table('collection_file_assets')->where('file_id', $file)->value('managed'));
        Capsule::table('news_comments')->where('id', $comment)->delete();
        $collection = $this->container()->get(CollectionsApiInterface::class)->getItem($report->articles[$article])->collectionId;
        $this->container()->get(\Johncms\Modules\News\Application\Collections\NewsDiscussionFiles::class)->cleanup([$file], $collection);
        self::assertSame(1, (int) Capsule::table('collection_file_assets')->where('file_id', $file)->value('managed'));
        self::assertSame(1, Capsule::table('files')->where('id', $file)->count());
    }

    public function testMalformedAttachmentsRollBackTheImport(): void
    {
        $this->article('Bad', attributes: ['attached_files' => '{broken']);
        try {
            $this->runImport();
            self::fail('Malformed legacy attachments must be repaired explicitly.');
        } catch (NewsLegacyImportException $error) {
            self::assertStringContainsString('Invalid JSON', $error->getMessage());
            self::assertSame(0, Capsule::table('collection_items')->count());
        }
    }

    public function testFullLegacySectionMetadataFitsWithoutTruncation(): void
    {
        $section = $this->section('Metadata');
        $keywords = str_repeat('ключ,', 5000);
        $description = str_repeat('description ', 5000);
        Capsule::table('news_sections')->where('id', $section)->update(['keywords' => $keywords, 'description' => $description]);
        $report = $this->runImport();
        $metadata = json_decode(Capsule::table('collection_sections')->where('id', $report->sections[$section])->value('metadata'), true);
        self::assertSame($keywords, $metadata['meta_keywords']);
        self::assertSame($description, $metadata['meta_description']);
    }

    public function testLegacyFilesWithoutOptionalSizeMetadataCanStillBeRegistered(): void
    {
        $file = $this->file('news');
        Capsule::table('files')->where('id', $file)->update(['size' => null]);
        $article = $this->article('Unsized', attributes: ['attached_files' => json_encode([$file])]);
        $report = $this->runImport();
        $item = $this->container()->get(CollectionsApiInterface::class)->getItem($report->articles[$article]);
        self::assertSame([$file], $this->container()->get(CollectionItemFiles::class)->registeredFileIds($item->collectionId, $item->id));
        self::assertNull(Capsule::table('files')->where('id', $file)->value('size'));
    }

    public function testImportRequiresTrustedSystemContext(): void
    {
        $this->expectException(\LogicException::class);
        $this->container()->get(NewsLegacyImporter::class)->import();
    }

    private function runImport(): \Johncms\Modules\News\Application\DTO\NewsLegacyImportReportDTO
    {
        return $this->container()->get(SystemOperationContext::class)->runTrusted(fn () => $this->container()->get(NewsLegacyImporter::class)->import());
    }

    private function section(?string $code, ?int $parent = null, bool $deleted = false, string $name = 'Section'): int
    {
        return Fixture::insert('news_sections', ['name' => $name, 'code' => $code, 'parent' => $parent, 'text' => 'Section <b>body</b>', 'keywords' => 'section keywords', 'description' => 'section description', 'created_at' => null, 'updated_at' => null, 'deleted_at' => $deleted ? '2001-01-01 00:00:00' : null]);
    }

    /** @param array<string, mixed> $attributes */
    private function article(string $code, ?int $section = null, array $attributes = []): int
    {
        return Fixture::insert('news_articles', $attributes + ['name' => 'Article', 'code' => $code, 'section_id' => $section, 'active' => true, 'text' => 'Raw <b>body</b> & text', 'created_at' => '2001-02-03 04:05:06', 'updated_at' => null]);
    }

    private function file(string $directory): int
    {
        return Fixture::insert('files', ['size' => 10, 'storage' => 'local', 'name' => 'photo.jpg', 'path' => $directory . '/legacy-photo.jpg']);
    }
}
