<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\ConnectionInterface;
use Johncms\Database\Schema\SchemaInterface;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The comments of the articles carried from news_comments into the comments of the core.
 *
 * The suite keeps news_comments as a site still waiting for the import of the news of 9.x has
 * it: with its article_id, so the migration that drops the table leaves it alone.
 */
final class NewsCommentsMigrationTest extends FunctionalTestCase
{
    private const string MIGRATION = 'modules/johncms/news/migrations/2026_11_14_212821_move_comments_to_core.php';

    public function testTheCommentsOfTheArticlesMoveWithTheirPicturesAndTheRestWaits(): void
    {
        $author = FunctionalUserFactory::create(['name' => 'Author', 'komm' => 3]);
        $attached = $this->file('news_comments/aa/bb/cc/attached.jpg');
        $shown = $this->file('news_comments/aa/bb/cc/shown.jpg');
        $ofArticle = $this->file('news_comments/aa/bb/cc/article.jpg');
        Fixture::insert('collection_file_links', ['file_id' => $ofArticle, 'item_id' => 5]);
        $first = $this->comment($author->id, 5, '<p><img src="/upload/news_comments/aa/bb/cc/shown.jpg"><img src="/upload/news_comments/aa/bb/cc/article.jpg"></p>', [$attached]);
        $second = $this->comment($author->id, 5, '<p>Again</p>', [$attached]);
        $this->comment($author->id, 5, '<p>Deleted</p>', [], deleted: true);
        $legacyArticle = Fixture::insert('news_articles', [
            'name'       => 'Old',
            'code'       => 'old',
            'section_id' => Fixture::insert('news_sections', ['code' => 'old', 'name' => 'Old']),
            'text'       => 'Body',
            'created_at' => '2001-01-01 00:00:00',
        ]);
        $waiting = $this->comment($author->id, null, '<p>Of an article of 9.x</p>', [], articleId: $legacyArticle);

        $this->runMigration();

        $moved = Capsule::table('comments')->where('subject_type', 'news')->orderBy('id')->get();
        self::assertSame(['<p><img src="/upload/news_comments/aa/bb/cc/shown.jpg"><img src="/upload/news_comments/aa/bb/cc/article.jpg"></p>', '<p>Again</p>'], $moved->pluck('text')->all());
        self::assertSame([5, 5], $moved->pluck('subject_id')->map(intval(...))->all());
        self::assertSame('Author', $moved[0]->author_name);
        self::assertSame('10.0.0.1', $moved[0]->author_ip);
        self::assertSame('Agent', $moved[0]->author_user_agent);
        self::assertSame(5, (int) Capsule::table('users')->where('id', $author->id)->value('komm'));

        $held = static fn(int $commentId): array => Capsule::table('editor_uploads')->where('owner_type', 'comment')->where('owner_id', $commentId)->orderBy('file_id')->pluck('file_id')->map(intval(...))->all();
        self::assertSame([$attached, $shown], $held((int) $moved[0]->id), 'The file the articles link stays with them.');
        $copy = $held((int) $moved[1]->id);
        self::assertCount(1, $copy);
        self::assertNotSame($attached, $copy[0], 'A file an earlier comment holds is a row of its own for the second.');
        self::assertSame('news_comments/aa/bb/cc/attached.jpg', Capsule::table('files')->where('id', $copy[0])->value('path'));

        self::assertSame([$waiting], Capsule::table('news_comments')->pluck('id')->map(intval(...))->all(), 'Only the comment waiting for the import stays.');
        self::assertFalse(Capsule::table('news_comments')->where('id', $first)->exists());
        self::assertFalse(Capsule::table('news_comments')->where('id', $second)->exists());
    }

    /**
     * @param list<int> $files
     */
    private function comment(int $userId, ?int $itemId, string $text, array $files, bool $deleted = false, ?int $articleId = null): int
    {
        return Fixture::insert('news_comments', [
            'article_id'     => $articleId,
            'item_id'        => $itemId,
            'user_id'        => $userId,
            'text'           => $text,
            'user_data'      => json_encode(['ip' => '10.0.0.1', 'ip_via_proxy' => '', 'user_agent' => 'Agent']),
            'created_at'     => '2026-01-01 12:00:00',
            'attached_files' => json_encode($files),
            'deleted_at'     => $deleted ? '2026-01-02 12:00:00' : null,
        ]);
    }

    private function file(string $path): int
    {
        return Fixture::insert('files', ['storage' => 'local', 'name' => basename($path), 'path' => $path]);
    }

    private function runMigration(): void
    {
        $db = $this->container()->get(ConnectionInterface::class);
        $migration = require ROOT_PATH . self::MIGRATION;
        $migration->bind($this->container()->get(SchemaInterface::class), $db);
        $migration->up();
    }
}
