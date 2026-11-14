<?php

declare(strict_types=1);

namespace Tests\Functional\Comments;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\ConnectionInterface;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The discussion of a photo: what the albums add to the comments of the core.
 */
final class AlbumDiscussionTest extends FunctionalTestCase
{
    private User $owner;

    private int $photoId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = FunctionalUserFactory::create();
        $this->photoId = $this->photo(access: 4);
    }

    public function testACommentOfSomebodyElseMarksThePhotoUnreadUntilTheOwnerOpensIt(): void
    {
        $this->postForm($this->url(), ['text' => '<p>Nice</p>'], FunctionalUserFactory::create());
        self::assertSame(1, (int) Capsule::table('cms_album_files')->where('id', $this->photoId)->value('unread_comments'));
        self::assertSame(1, (int) Capsule::table('cms_album_files')->where('id', $this->photoId)->value('comm_count'));

        $this->handleRequest($this->url(), cookies: $this->actingAs($this->owner));

        self::assertSame(0, (int) Capsule::table('cms_album_files')->where('id', $this->photoId)->value('unread_comments'));
    }

    public function testTheOwnerOfThePhotoDeletesTheCommentsUnderIt(): void
    {
        $id = $this->comment(FunctionalUserFactory::create()->id);

        $this->postForm($this->url() . '/delete', ['comment_id' => $id, 'confirmed' => '1'], $this->owner);

        self::assertFalse(Capsule::table('comments')->where('id', $id)->exists());
    }

    public function testAPrivateAlbumHasNoDiscussionForAStranger(): void
    {
        $private = $this->photo(access: 1);

        $response = $this->handleRequest('/album/photo/' . $private . '/comments', cookies: $this->actingAs(FunctionalUserFactory::create()));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testTheOwnerOnTheirIgnoreListKeepsTheIgnoredOut(): void
    {
        $ignored = FunctionalUserFactory::create();
        Fixture::insert('cms_contact', ['user_id' => $this->owner->id, 'from_id' => $ignored->id, 'ban' => 1, 'time' => time()]);

        $this->postForm($this->url(), ['text' => '<p>Hello</p>'], $ignored);

        self::assertSame(0, Capsule::table('comments')->where('subject_type', 'album')->count());
    }

    public function testDeletingThePhotoDeletesItsComments(): void
    {
        $id = $this->comment(FunctionalUserFactory::create()->id);

        $this->postForm('/album/photo/' . $this->photoId . '/delete', ['confirmed' => '1'], $this->owner);

        self::assertFalse(Capsule::table('cms_album_files')->where('id', $this->photoId)->exists());
        self::assertFalse(Capsule::table('comments')->where('id', $id)->exists());
    }

    public function testTheTopOfRecentCommentsReadsTheCommentsOfTheCore(): void
    {
        $this->comment(FunctionalUserFactory::create()->id);

        $page = (string) $this->handleRequest('/album/top/recent-comments', cookies: $this->actingAs($this->owner))->getContent();

        self::assertStringContainsString('/album/photo/' . $this->photoId, $page);
    }

    public function testTheOldCommentsMoveIntoTheCore(): void
    {
        $db = $this->container()->get(ConnectionInterface::class);
        $db->execute(
            'CREATE TEMPORARY TABLE cms_album_comments (id INTEGER NOT NULL PRIMARY KEY, sub_id INTEGER NOT NULL, time INTEGER NOT NULL,'
            . ' user_id INTEGER NOT NULL, text TEXT NOT NULL, reply TEXT NOT NULL, attributes TEXT NOT NULL)'
        );
        try {
            $db->execute(
                'INSERT INTO cms_album_comments (id, sub_id, time, user_id, text, reply, attributes) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [1, $this->photoId, 1_700_000_000, $this->owner->id, '<p>Old</p>', '', serialize(['author_name' => 'Old'])]
            );

            $migration = require ROOT_PATH . 'modules/johncms/album/migrations/2026_11_14_170921_move_comments_to_core.php';
            $migration->bind($this->container()->get(SchemaInterface::class), $db);
            $migration->up();
        } finally {
            $db->execute(Capsule::connection()->getDriverName() === 'sqlite' ? 'DROP TABLE cms_album_comments' : 'DROP TEMPORARY TABLE cms_album_comments');
        }

        self::assertSame(['<p>Old</p>'], Capsule::table('comments')->where('subject_type', 'album')->pluck('text')->all());
        self::assertSame(1, (int) Capsule::table('cms_album_files')->where('id', $this->photoId)->value('comm_count'));
    }

    private function url(): string
    {
        return '/album/photo/' . $this->photoId . '/comments';
    }

    private function photo(int $access): int
    {
        $albumId = Fixture::insert('cms_album_cat', ['user_id' => $this->owner->id, 'name' => 'An album', 'access' => $access]);

        return Fixture::insert('cms_album_files', [
            'user_id'  => $this->owner->id,
            'album_id' => $albumId,
            'img_name' => 'photo.jpg',
            'tmb_name' => 'photo_tmb.jpg',
            'access'   => $access,
        ]);
    }

    private function comment(int $userId): int
    {
        return Fixture::insert('comments', [
            'subject_type' => 'album',
            'subject_id'   => $this->photoId,
            'user_id'      => $userId,
            'text'         => '<p>A comment</p>',
            'author_name'  => 'Author',
            'created_at'   => gmdate('Y-m-d H:i:s'),
        ]);
    }
}
