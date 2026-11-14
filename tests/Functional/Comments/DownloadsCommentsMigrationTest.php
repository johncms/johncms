<?php

declare(strict_types=1);

namespace Tests\Functional\Comments;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\ConnectionInterface;
use Johncms\Database\Schema\SchemaInterface;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The comments of the files carried from download__comments into the comments of the core.
 *
 * The suite has already run the migration and dropped the old table, so the table comes back as a
 * temporary one for the test: no DDL that MySQL would commit the test transaction for.
 */
final class DownloadsCommentsMigrationTest extends FunctionalTestCase
{
    private const string MIGRATION = 'modules/johncms/downloads/migrations/2026_11_14_165343_move_comments_to_core.php';

    protected function setUp(): void
    {
        parent::setUp();

        $this->db()->execute(
            'CREATE TEMPORARY TABLE download__comments (id INTEGER NOT NULL PRIMARY KEY, sub_id INTEGER NOT NULL, time INTEGER NOT NULL,'
            . ' user_id INTEGER NOT NULL, text TEXT NOT NULL, reply TEXT NOT NULL, attributes TEXT NOT NULL)'
        );
    }

    protected function tearDown(): void
    {
        $sqlite = Capsule::connection()->getDriverName() === 'sqlite';
        $this->db()->execute($sqlite ? 'DROP TABLE download__comments' : 'DROP TEMPORARY TABLE download__comments');

        parent::tearDown();
    }

    public function testACommentKeepsWhatTheOldEngineKnewAboutIt(): void
    {
        $author = FunctionalUserFactory::create(['name' => 'Author']);
        $file = Fixture::insert('download__files', ['type' => 2, 'name' => 'file.zip', 'rus_name' => 'A file', 'comm_count' => 7]);
        $this->old(1, $file, $author->id, 1_700_000_000, '<p>Hello</p>', '', [
            'author_name'         => 'Old name',
            'author_ip'           => ip2long('10.1.2.3'),
            'author_ip_via_proxy' => 0,
            'author_browser'      => 'Agent',
            'edit_id'             => $author->id,
            'edit_name'           => 'Editor',
            'edit_time'           => 1_700_000_100,
            'edit_count'          => 2,
        ]);

        $this->runMigration();

        $row = Capsule::table('comments')->where('subject_type', 'downloads')->first();
        self::assertNotNull($row);
        self::assertSame($file, (int) $row->subject_id);
        self::assertSame('Old name', $row->author_name);
        self::assertSame('10.1.2.3', $row->author_ip);
        self::assertNull($row->author_ip_via_proxy);
        self::assertSame('Agent', $row->author_user_agent);
        self::assertSame(gmdate('Y-m-d H:i:s', 1_700_000_000), $row->created_at);
        self::assertSame('Editor', $row->editor_name);
        self::assertSame(2, (int) $row->edit_count);
        self::assertSame(1, (int) Capsule::table('download__files')->where('id', $file)->value('comm_count'));
    }

    public function testAReplyBecomesACommentOfItsAuthorInItsPlaceInTime(): void
    {
        $author = FunctionalUserFactory::create();
        $moderator = FunctionalUserFactory::create(['komm' => 5]);
        $file = Fixture::insert('download__files', ['type' => 2, 'name' => 'file.zip', 'rus_name' => 'A file']);
        $this->old(1, $file, $author->id, 1_700_000_000, '<p>Question</p>', '<p>Answer</p>', [
            'author_name' => 'Author',
            'reply_id'    => $moderator->id,
            'reply_name'  => 'Moderator',
            'reply_time'  => 1_700_090_000,
            'reply_staff' => true,
        ]);
        $this->old(2, $file, $author->id, 1_700_050_000, '<p>Next</p>', '', ['author_name' => 'Author']);

        $this->runMigration();

        $rows = Capsule::table('comments')->where('subject_type', 'downloads')->orderBy('created_at')->orderBy('id')->get();
        self::assertSame(['<p>Question</p>', '<p>Next</p>', '<p>Answer</p>'], $rows->pluck('text')->all());
        $reply = $rows[2];
        self::assertSame($moderator->id, (int) $reply->user_id);
        self::assertSame('Moderator', $reply->author_name);
        self::assertSame(1, (int) $reply->staff_smilies);
        self::assertSame(6, (int) Capsule::table('users')->where('id', $moderator->id)->value('komm'));
        self::assertSame(3, (int) Capsule::table('download__files')->where('id', $file)->value('comm_count'));
    }

    public function testTheReplyOfADeletedAuthorKeepsTheirName(): void
    {
        $file = Fixture::insert('download__files', ['type' => 2, 'name' => 'file.zip', 'rus_name' => 'A file']);
        $this->old(1, $file, 0, 1_700_000_000, '<p>Question</p>', '<p>Answer</p>', [
            'reply_id'   => 999999,
            'reply_name' => 'Gone',
        ]);

        $this->runMigration();

        $reply = Capsule::table('comments')->where('text', '<p>Answer</p>')->first();
        self::assertNotNull($reply);
        self::assertSame(999999, (int) $reply->user_id);
        self::assertSame('Gone', $reply->author_name);
        self::assertSame(gmdate('Y-m-d H:i:s', 1_700_000_001), $reply->created_at);
    }

    public function testDamagedAttributesNeverGiveTheReplyToTheAuthorOfTheComment(): void
    {
        $author = FunctionalUserFactory::create(['name' => 'Author']);
        $file = Fixture::insert('download__files', ['type' => 2, 'name' => 'file.zip', 'rus_name' => 'A file']);
        $this->db()->execute(
            'INSERT INTO download__comments (id, sub_id, time, user_id, text, reply, attributes) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [1, $file, 1_700_000_000, $author->id, '<p>Question</p>', '<p>Answer</p>', 'a:1:{s:3:"bad']
        );

        $this->runMigration();

        $comment = Capsule::table('comments')->where('text', '<p>Question</p>')->first();
        $reply = Capsule::table('comments')->where('text', '<p>Answer</p>')->first();
        self::assertSame('Author', $comment?->author_name);
        self::assertSame(0, (int) $reply?->user_id);
        self::assertSame('', $reply?->author_name);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function old(int $id, int $file, int $userId, int $time, string $text, string $reply, array $attributes): void
    {
        $this->db()->execute(
            'INSERT INTO download__comments (id, sub_id, time, user_id, text, reply, attributes) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$id, $file, $time, $userId, $text, $reply, serialize($attributes)]
        );
    }

    private function runMigration(): void
    {
        $migration = require ROOT_PATH . self::MIGRATION;
        $migration->bind($this->container()->get(SchemaInterface::class), $this->db());
        $migration->up();
    }

    private function db(): ConnectionInterface
    {
        return $this->container()->get(ConnectionInterface::class);
    }
}
