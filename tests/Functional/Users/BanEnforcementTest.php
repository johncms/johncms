<?php

declare(strict_types=1);

namespace Tests\Functional\Users;

use Dom\HTMLDocument;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Users\User;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * What a ban of each kind takes away from a member: the sections they may read and the places
 * they may write in. A place is told open by the form it offers, the same check that guards the
 * submission behind it.
 *
 * No ban takes away what a guest may read. A full ban closes every place a member writes in, a
 * ban of a section closes the places of that section.
 */
final class BanEnforcementTest extends FunctionalTestCase
{
    /** Full block, private messages, comments, forum, guestbook, library. */
    private const array KINDS = [1, 3, 10, 11, 13, 15];

    /** The sections a guest reads, each behind a permission of its own. */
    private const array SECTIONS = ['/forum/', '/library/', '/downloads/', '/community/'];

    public function testAMemberWithoutABanReadsEverySection(): void
    {
        $member = FunctionalUserFactory::create();

        foreach (self::SECTIONS as $path) {
            self::assertSame(200, $this->statusOf($path, $member), $path);
        }
    }

    #[DataProvider('kinds')]
    public function testNoBanClosesWhatAGuestReads(int $kind): void
    {
        $member = $this->bannedMember($kind);

        foreach (self::SECTIONS as $path) {
            self::assertSame(200, $this->statusOf($path, $member), $path);
        }
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function kinds(): iterable
    {
        foreach (self::KINDS as $kind) {
            yield 'kind ' . $kind => [$kind];
        }
    }

    /**
     * @param list<int> $closedBy The kinds of ban that take the place away.
     */
    #[DataProvider('places')]
    public function testABanClosesThePlacesOfAMember(string $place, array $closedBy): void
    {
        foreach ([0, ...self::KINDS] as $kind) {
            $member = $this->bannedMember($kind);

            self::assertSame(
                ! in_array($kind, $closedBy, true),
                $this->offersForm($place, $member),
                sprintf('%s with a ban of kind %d', $place, $kind)
            );
        }
    }

    /**
     * @return iterable<string, array{string, list<int>}>
     */
    public static function places(): iterable
    {
        yield 'a new topic of the forum' => ['forum topic', [1, 11]];
        yield 'the last post of their own on the forum' => ['forum post', [1, 11]];
        yield 'the guestbook' => ['guestbook', [1, 13]];
        yield 'a new article of the library' => ['library article', [1, 15]];
        yield 'a comment on an article of the library' => ['library comment', [1, 10, 15]];
        yield 'a comment on a photo' => ['photo comment', [1, 10]];
        yield 'a file for the downloads' => ['downloads file', [1]];
        yield 'a photo for an album of their own' => ['album photo', [1]];
        yield 'their own profile' => ['profile', [1]];
    }

    public function testAnAdministratorBannedFromTheForumKeepsThePanel(): void
    {
        $admin = FunctionalUserFactory::create([], [SystemRole::Admin->value]);
        $this->ban($admin, 11);

        self::assertSame(200, $this->statusOf('/admin/', $admin));
    }

    /**
     * A full ban outranks the roles: a banned administrator administers nothing.
     */
    public function testAnAdministratorBannedFromTheSiteLosesThePanel(): void
    {
        $admin = FunctionalUserFactory::create([], [SystemRole::Admin->value]);
        $this->ban($admin, 1);

        self::assertSame(403, $this->statusOf('/admin/', $admin));
    }

    private function bannedMember(int $kind): User
    {
        $member = FunctionalUserFactory::create();

        if ($kind !== 0) {
            $this->ban($member, $kind);
        }

        return $member;
    }

    private function ban(User $user, int $kind): void
    {
        Fixture::insert(
            'cms_ban_users',
            ['user_id' => $user->id, 'ban_time' => time() + 3600, 'ban_while' => time(), 'ban_type' => $kind, 'ban_reason' => 'Spam']
        );
    }

    private function statusOf(string $path, User $user): int
    {
        return $this->handleRequest($path, cookies: $this->actingAs($user))->getStatusCode();
    }

    private function offersForm(string $place, User $member): bool
    {
        [$path, $selector] = $this->place($place, $member);
        $response = $this->handleRequest($path, cookies: $this->actingAs($member));
        $page = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);

        return $page->querySelector($selector) !== null;
    }

    /**
     * Writes what the place needs and answers where it is and what its form is told by.
     *
     * @return array{string, string}
     */
    private function place(string $place, User $member): array
    {
        return match ($place) {
            'forum topic' => [
                '/forum/new-topic/' . Fixture::insert('forum_sections', ['name' => 'Talk', 'parent' => 0, 'section_type' => 1]),
                '[name="th"]',
            ],
            'forum post' => ['/forum/edit-post/' . $this->postOf($member), 'form[name="edit_post"]'],
            'guestbook' => ['/guestbook', 'form[action="/guestbook/"]'],
            'library article' => [
                '/library/article/create?id=' . Fixture::insert('library_cats', ['name' => 'Shelf', 'dir' => 0, 'user_add' => 1]),
                'form [name="announce"]',
            ],
            'library comment' => ['/library/article/' . $this->article() . '/comments', 'form [name="code"]'],
            'photo comment' => ['/album/photo/' . $this->photoOf($member) . '/comments', 'form [name="code"]'],
            'downloads file' => [
                '/downloads/upload/' . Fixture::insert(
                    'download__category',
                    // A filled field lets the members upload into the folder, in the formats of the text.
                    ['refid' => 0, 'name' => 'Files', 'rus_name' => 'Files', 'dir' => sys_get_temp_dir(), 'field' => 1, 'text' => 'zip']
                ),
                'form[name="file_upload_form"]',
            ],
            'album photo' => [
                '/album/' . Fixture::insert('cms_album_cat', ['user_id' => $member->id, 'name' => 'Mine', 'access' => 4]) . '/upload',
                'form input[type="file"]',
            ],
            'profile' => ['/profile/' . $member->id . '/edit', 'form [name="imname"]'],
            default => throw new LogicException(sprintf('There is no place called "%s".', $place)),
        };
    }

    /**
     * The last post of a topic, written a moment ago: the one its author may still change.
     */
    private function postOf(User $member): int
    {
        $section = Fixture::insert('forum_sections', ['name' => 'Talk', 'parent' => 0, 'section_type' => 1]);
        $topic = Fixture::insert('forum_topic', ['section_id' => $section, 'name' => 'A topic', 'user_id' => $member->id]);

        return Fixture::insert('forum_messages', ['topic_id' => $topic, 'user_id' => $member->id, 'date' => time(), 'text' => 'Hello']);
    }

    private function photoOf(User $member): int
    {
        $album = Fixture::insert('cms_album_cat', ['user_id' => $member->id, 'name' => 'Mine', 'access' => 4]);

        return Fixture::insert('cms_album_files', ['user_id' => $member->id, 'album_id' => $album, 'img_name' => 'photo.jpg', 'access' => 4]);
    }

    private function article(): int
    {
        $shelf = Fixture::insert('library_cats', ['name' => 'Shelf']);

        return Fixture::insert('library_texts', ['cat_id' => $shelf, 'name' => 'An article', 'text' => 'Body', 'comments' => 1]);
    }
}
