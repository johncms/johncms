<?php

declare(strict_types=1);

namespace Tests\Functional\Guestbook;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\ConnectionInterface;
use Johncms\Database\Schema\SchemaInterface;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The replies of the staff become entries that answer the entry they were written under.
 */
final class GuestbookStaffRepliesMigrationTest extends FunctionalTestCase
{
    public function testEveryReplyBecomesAnAnswerOfItsEntry(): void
    {
        $staff = FunctionalUserFactory::createSupervisor();
        $author = FunctionalUserFactory::create();
        $twins = [FunctionalUserFactory::create()->id, FunctionalUserFactory::create()->id];
        Capsule::table('users')->whereIn('id', $twins)->update(['name' => 'Twin']);
        $postsBefore = (int) Capsule::table('users')->where('id', $staff->id)->value('postguest');

        $both = $this->file('guestbook/aa/bb/cc/both.jpg');
        $replyOnly = $this->file('guestbook/aa/bb/cc/reply.jpg');
        $textOnly = $this->file('guestbook/aa/bb/cc/text.jpg');
        $role = (int) Capsule::table('roles')->value('id');
        Capsule::table('role_permissions')->insert(['role_id' => $role, 'permission' => 'guestbook.entry.reply']);

        $rows = $this->runOverOldTable(
            [
                [1, 0, 1000, $author->id, 'Author', '<p><img src="/upload/guestbook/aa/bb/cc/both.jpg"><img src="/upload/guestbook/aa/bb/cc/text.jpg"></p>', $staff->name, '<p><img src="/upload/guestbook/aa/bb/cc/both.jpg"><img src="/upload/guestbook/aa/bb/cc/reply.jpg"></p>', 2000],
                [2, 1, 1000, $author->id, 'Author', '<p>In the club</p>', 'Twin', '<p>Of one of the twins</p>', 0],
                [3, 0, 1000, $author->id, 'Author', '<p>Unanswered</p>', '', '', 0],
                [4, 0, 1000, 0, 'Guest', '<p>Blank reply</p>', $staff->name, " \n", 3000],
            ],
            [[$both, 1], [$replyOnly, 1], [$textOnly, 1]],
        );

        self::assertCount(6, $rows, 'Two replies became entries; a blank one did not.');
        $first = $rows[4];
        self::assertSame(1, (int) $first['reply_to_id']);
        self::assertSame(0, (int) $first['adm']);
        self::assertSame(2000, (int) $first['time']);
        self::assertSame($staff->id, (int) $first['user_id'], 'The only account of the name is the author.');
        self::assertSame($staff->name, $first['name']);
        self::assertStringContainsString('reply.jpg', (string) $first['text']);

        $second = $rows[5];
        self::assertSame(2, (int) $second['reply_to_id']);
        self::assertSame(1, (int) $second['adm'], 'An answer stays in its guestbook.');
        self::assertSame(1001, (int) $second['time'], 'A reply without a time comes right after its entry.');
        self::assertSame(0, (int) $second['user_id'], 'A name several accounts share gives the reply to none of them.');

        self::assertSame($postsBefore + 1, (int) Capsule::table('users')->where('id', $staff->id)->value('postguest'));
        self::assertFalse(Capsule::table('role_permissions')->where('permission', 'guestbook.entry.reply')->exists());

        $answer = (int) $first['id'];
        self::assertSame([1], $this->owners($textOnly), 'A picture of the text alone stays with the entry.');
        self::assertSame([$answer], $this->owners($replyOnly), 'A picture of the reply alone moves to the answer.');
        self::assertSame([1], $this->owners($both));
        $copy = Capsule::table('editor_uploads')->where('owner_type', 'guestbook')->where('owner_id', $answer)->where('file_id', '<>', $replyOnly)->value('file_id');
        self::assertNotNull($copy, 'A picture both show is given to the answer as a copy.');
        self::assertNotSame($both, (int) $copy);
        self::assertSame('guestbook/aa/bb/cc/both.jpg', Capsule::table('files')->where('id', $copy)->value('path'));
    }

    /**
     * Runs the migration over a table in the shape it had before, and answers its rows.
     *
     * @param list<array{int, int, int, int, string, string, string, string, int}> $entries
     * @param list<array{int, int}> $pictures File and entry holding it.
     * @return list<array<string, mixed>>
     */
    private function runOverOldTable(array $entries, array $pictures): array
    {
        $db = $this->container()->get(ConnectionInterface::class);
        self::assertInstanceOf(ConnectionInterface::class, $db);

        // A temporary table of the same name hides the real one, whose columns are already dropped.
        $db->execute(
            'CREATE TEMPORARY TABLE guest (id INTEGER NOT NULL PRIMARY KEY' . (Capsule::connection()->getDriverName() === 'sqlite' ? '' : ' AUTO_INCREMENT') . ','
            . ' adm INTEGER NOT NULL, time INTEGER NOT NULL, user_id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, text TEXT NOT NULL,'
            . ' ip BIGINT NOT NULL, browser VARCHAR(255) NOT NULL, admin VARCHAR(255) NOT NULL, otvet TEXT NOT NULL, otime INTEGER NOT NULL,'
            . ' edit_who VARCHAR(255) NOT NULL, edit_time INTEGER NOT NULL, edit_count INTEGER NOT NULL, reply_to_id INTEGER NULL)'
        );
        try {
            foreach ($entries as [$id, $adm, $time, $userId, $name, $text, $admin, $reply, $replyTime]) {
                $db->execute(
                    "INSERT INTO guest (id, adm, time, user_id, name, text, ip, browser, admin, otvet, otime, edit_who, edit_time, edit_count) VALUES (?, ?, ?, ?, ?, ?, 0, '', ?, ?, ?, '', 0, 0)",
                    [$id, $adm, $time, $userId, $name, $text, $admin, $reply, $replyTime]
                );
            }
            foreach ($pictures as [$file, $entry]) {
                Capsule::table('editor_uploads')->insert([
                    'file_id'    => $file,
                    'user_id'    => 0,
                    'directory'  => 'guestbook',
                    'owner_type' => 'guestbook',
                    'owner_id'   => $entry,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $migration = require ROOT_PATH . 'modules/johncms/guestbook/migrations/2026_11_15_173129_move_staff_replies_to_entries.php';
            $migration->bind($this->container()->get(SchemaInterface::class), $db);
            $migration->up();

            return $db->select('SELECT * FROM guest ORDER BY id');
        } finally {
            $db->execute(Capsule::connection()->getDriverName() === 'sqlite' ? 'DROP TABLE temp.guest' : 'DROP TEMPORARY TABLE guest');
        }
    }

    /**
     * @return list<int>
     */
    private function owners(int $fileId): array
    {
        return Capsule::table('editor_uploads')
            ->where('file_id', $fileId)
            ->where('owner_type', 'guestbook')
            ->pluck('owner_id')
            ->map(intval(...))
            ->all();
    }

    private function file(string $path): int
    {
        return Fixture::insert('files', ['storage' => 'local', 'name' => basename($path), 'path' => $path]);
    }
}
