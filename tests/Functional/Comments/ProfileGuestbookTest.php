<?php

declare(strict_types=1);

namespace Tests\Functional\Comments;

use Dom\HTMLDocument;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\ConnectionInterface;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Modules\Profile\Domain\Repository\GuestbookReadRepositoryInterface;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The guestbook of a profile, kept as the comments of the core: what is unread for its owner, and
 * what the owner may do in it.
 */
final class ProfileGuestbookTest extends FunctionalTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = FunctionalUserFactory::create();
    }

    public function testOpeningTheGuestbookReadsWhatOthersWrote(): void
    {
        $guest = FunctionalUserFactory::create()->id;
        $this->entry($guest);
        $this->entry($guest);
        self::assertSame(2, $this->unread());

        $this->handleRequest($this->url(), cookies: $this->actingAs($this->owner));

        self::assertSame(0, $this->unread());
    }

    public function testDeletedOldEntriesDoNotHideTheNewOnes(): void
    {
        $guest = FunctionalUserFactory::create()->id;
        $read = [];
        for ($i = 0; $i < 10; $i++) {
            $read[] = $this->entry($guest);
        }
        $this->handleRequest($this->url(), cookies: $this->actingAs($this->owner));
        $this->entry($guest);
        $this->entry($guest);

        Capsule::table('comments')->whereIn('id', array_slice($read, 0, 3))->delete();

        self::assertSame(2, $this->unread());
    }

    public function testEntriesOfOneSecondAreBothUnreadAndTheOwnOnesNever(): void
    {
        $guest = FunctionalUserFactory::create()->id;
        $this->handleRequest($this->url(), cookies: $this->actingAs($this->owner));
        $time = gmdate('Y-m-d H:i:s');
        $this->entry($guest, $time);
        $this->entry($guest, $time);
        $this->entry($this->owner->id, $time);

        self::assertSame(2, $this->unread());
    }

    public function testTheGuestbookSpeaksOfEntries(): void
    {
        $this->entry(FunctionalUserFactory::create()->id);

        $page = (string) $this->handleRequest($this->url(), cookies: $this->actingAs($this->owner))->getContent();

        $document = HTMLDocument::createFromString($page, LIBXML_NOERROR);
        self::assertSame(sprintf(dn__('profile', '%d entry', '%d entries', 1), 1), trim((string) $document->querySelector('.site-discussion__count')?->textContent));
        self::assertSame(d__('profile', 'Write an entry'), $document->querySelector('#comment-form-title')?->textContent);
        self::assertSame(d__('profile', 'Delete this entry?'), $document->querySelector('form[data-confirm]')?->getAttribute('data-confirm'));
        self::assertStringStartsWith(sprintf(d__('profile', 'Guestbook: %s'), $this->owner->name), (string) $document->querySelector('title')?->textContent);
    }

    public function testAnEntryIsCountedOnTheAccount(): void
    {
        $this->postForm($this->url(), ['text' => '<p>Hello</p>'], FunctionalUserFactory::create());

        self::assertSame(1, (int) Capsule::table('users')->where('id', $this->owner->id)->value('comm_count'));
        self::assertSame(1, $this->unread());
    }

    public function testTheOwnerDeletesTheEntriesOfTheirGuestbook(): void
    {
        $id = $this->entry(FunctionalUserFactory::create()->id);

        $this->postForm($this->url() . '/delete', ['comment_id' => $id, 'confirmed' => '1'], $this->owner);

        self::assertFalse(Capsule::table('comments')->where('id', $id)->exists());
    }

    public function testTheGuestbooksMoveIntoTheCoreAndStartRead(): void
    {
        $guest = FunctionalUserFactory::create(['komm' => 0]);
        $db = $this->container()->get(ConnectionInterface::class);
        $db->execute(
            'CREATE TEMPORARY TABLE cms_users_guestbook (id INTEGER NOT NULL PRIMARY KEY, sub_id INTEGER NOT NULL, time INTEGER NOT NULL,'
            . ' user_id INTEGER NOT NULL, text TEXT NOT NULL, reply TEXT NOT NULL, attributes TEXT NOT NULL)'
        );
        try {
            $db->execute(
                'INSERT INTO cms_users_guestbook (id, sub_id, time, user_id, text, reply, attributes) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [1, $this->owner->id, 1_700_000_000, $guest->id, '<p>Hi</p>', '<p>Thanks</p>', serialize(['author_name' => 'Guest', 'reply_id' => $this->owner->id, 'reply_name' => 'Owner', 'reply_time' => 1_700_000_100])]
            );
            $this->applyMigration('system/migrations/2026_11_14_171258_move_profile_guestbooks_to_comments.php', $db);
        } finally {
            $db->execute(Capsule::connection()->getDriverName() === 'sqlite' ? 'DROP TABLE cms_users_guestbook' : 'DROP TEMPORARY TABLE cms_users_guestbook');
        }
        $this->applyMigration('modules/johncms/profile/migrations/2026_11_14_171248_mark_guestbooks_read.php', $db);

        self::assertSame(2, (int) Capsule::table('users')->where('id', $this->owner->id)->value('comm_count'));
        self::assertSame(0, $this->unread());
        self::assertSame(['<p>Hi</p>', '<p>Thanks</p>'], Capsule::table('comments')->where('subject_type', 'profile_guestbook')->orderBy('created_at')->pluck('text')->all());
    }

    private function applyMigration(string $file, ConnectionInterface $db): void
    {
        $migration = require ROOT_PATH . $file;
        $migration->bind($this->container()->get(SchemaInterface::class), $db);
        $migration->up();
    }

    private function url(): string
    {
        return '/profile/' . $this->owner->id . '/guestbook';
    }

    private function unread(): int
    {
        return $this->container()->get(GuestbookReadRepositoryInterface::class)->countUnread($this->owner->id);
    }

    private function entry(int $authorId, ?string $createdAt = null): int
    {
        return Fixture::insert('comments', [
            'subject_type' => 'profile_guestbook',
            'subject_id'   => $this->owner->id,
            'user_id'      => $authorId,
            'text'         => '<p>An entry</p>',
            'author_name'  => 'Author',
            'created_at'   => $createdAt ?? gmdate('Y-m-d H:i:s'),
        ]);
    }
}
