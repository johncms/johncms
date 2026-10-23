<?php

declare(strict_types=1);

namespace Tests\Functional\Notifications;

use Dom\HTMLDocument;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * What waits for a signed-in visitor: the list on the notifications page, one line per thing with
 * a link to where it is, and the badge of the header adding them up.
 *
 * A line is told by the address it leads to, not by its words: the site answers in its own
 * language.
 */
final class SystemNotificationsTest extends FunctionalTestCase
{
    public function testNothingWaitsForAMemberWithNothingToDo(): void
    {
        $page = $this->notificationsOf(FunctionalUserFactory::create());

        self::assertSame([], $this->listed($page));
        self::assertNull($this->badge($page));
    }

    public function testTheLettersAMemberHasNotReadAreCounted(): void
    {
        $member = FunctionalUserFactory::create();
        $sender = FunctionalUserFactory::create();
        Fixture::insert('cms_contact', ['user_id' => $member->id, 'from_id' => $sender->id]);
        Fixture::insert('cms_mail', ['user_id' => $sender->id, 'from_id' => $member->id, 'text' => 'One']);
        Fixture::insert('cms_mail', ['user_id' => $sender->id, 'from_id' => $member->id, 'text' => 'Two']);

        $page = $this->notificationsOf($member);

        self::assertSame(['/mail/incoming'], $this->listed($page));
        self::assertSame('2', $this->badge($page));
    }

    public function testTheStaffAreShownTheQueuesTheyLookAfter(): void
    {
        FunctionalUserFactory::create(['preg' => 0]);
        Fixture::insert('library_texts', ['name' => 'Waiting', 'premod' => 0]);
        Fixture::insert('download__files', ['name' => 'Waiting', 'type' => 3]);

        $page = $this->notificationsOf(FunctionalUserFactory::createSupervisor());

        self::assertEqualsCanonicalizing(['/admin/reg/', '/library/premod', '/downloads/moderation'], $this->listed($page));
        self::assertSame('3', $this->badge($page));
    }

    public function testAMemberIsNotShownTheQueuesOfTheStaff(): void
    {
        FunctionalUserFactory::create(['preg' => 0]);
        Fixture::insert('library_texts', ['name' => 'Waiting', 'premod' => 0]);
        Fixture::insert('download__files', ['name' => 'Waiting', 'type' => 3]);

        self::assertSame([], $this->listed($this->notificationsOf(FunctionalUserFactory::create())));
    }

    /**
     * The ban is a line of its own without a number on it, and still counts one in the header.
     */
    public function testABannedMemberIsShownWhereTheBanIs(): void
    {
        $member = FunctionalUserFactory::create();
        Fixture::insert('cms_ban_users', ['user_id' => $member->id, 'ban_time' => time() + 3600, 'ban_reason' => 'Spam']);

        $page = $this->notificationsOf($member);

        self::assertSame(['/profile/' . $member->id . '/bans'], $this->listed($page));
        self::assertSame('1', $this->badge($page));
    }

    public function testTheNewEntriesInTheGuestbookOfAMemberAreCounted(): void
    {
        $member = FunctionalUserFactory::create(['comm_count' => 3, 'comm_old' => 1]);

        $page = $this->notificationsOf($member);

        self::assertSame(['/profile/' . $member->id . '/guestbook'], $this->listed($page));
        self::assertSame('2', $this->badge($page));
    }

    public function testTheUnreadCommentsOnThePhotosOfAMemberAreCounted(): void
    {
        $member = FunctionalUserFactory::create();
        Fixture::insert('cms_album_files', ['user_id' => $member->id, 'unread_comments' => 1]);

        $page = $this->notificationsOf($member);

        self::assertSame(['/album/top/my-comments'], $this->listed($page));
        self::assertSame('1', $this->badge($page));
    }

    public function testTheUnreadTopicsOfTheForumAreShownToAMemberWhoAskedForThem(): void
    {
        Fixture::insert('forum_topic', ['name' => 'Unread', 'last_post_date' => time()]);

        $asked = FunctionalUserFactory::create(['notification_settings' => json_encode(['show_forum_unread' => true])]);
        $notAsked = FunctionalUserFactory::create();

        self::assertSame(['/forum/?act=new'], $this->listed($this->notificationsOf($asked)));
        self::assertSame([], $this->listed($this->notificationsOf($notAsked)));
    }

    private function notificationsOf(User $user): HTMLDocument
    {
        $response = $this->handleRequest('/notifications', cookies: $this->actingAs($user));

        return HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    }

    /**
     * @return list<string> Where the lines of the list lead.
     */
    private function listed(HTMLDocument $page): array
    {
        $links = [];

        foreach ($page->querySelectorAll('a.list-item') as $link) {
            $links[] = (string) $link->getAttribute('href');
        }

        return $links;
    }

    private function badge(HTMLDocument $page): ?string
    {
        return $page->querySelector('a.icon_with_badge[href="/notifications/"] .badge')?->textContent;
    }
}
