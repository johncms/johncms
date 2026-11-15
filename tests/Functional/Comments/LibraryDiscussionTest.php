<?php

declare(strict_types=1);

namespace Tests\Functional\Comments;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\ConnectionInterface;
use Johncms\Database\Schema\SchemaInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The discussion of an article of the library: what the library adds to the comments of the core.
 */
final class LibraryDiscussionTest extends FunctionalTestCase
{
    private int $uploaderId;

    private int $articleId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploaderId = FunctionalUserFactory::create()->id;
        $this->articleId = $this->article();
    }

    public function testAGuestReadsThePublishedDiscussion(): void
    {
        $this->comment(FunctionalUserFactory::create()->id);

        self::assertSame(Response::HTTP_OK, $this->handleRequest($this->url())->getStatusCode());
    }

    public function testAnArticleAwaitingModerationIsDiscussedByTheModeratorsOnly(): void
    {
        $hidden = $this->article(published: false);
        $url = '/library/article/' . $hidden . '/comments';

        self::assertSame(Response::HTTP_NOT_FOUND, $this->handleRequest($url, cookies: $this->actingAs(FunctionalUserFactory::create()))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $this->handleRequest($url, cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getStatusCode());
    }

    public function testTheUploaderDeletesTheCommentsUnderTheirArticle(): void
    {
        $id = $this->comment(FunctionalUserFactory::create()->id);

        $this->postForm($this->url() . '/delete', ['comment_id' => $id, 'confirmed' => '1'], $this->uploaderId);

        self::assertFalse(Capsule::table('comments')->where('id', $id)->exists());
    }

    public function testTheBanOfTheLibraryClosesTheDiscussion(): void
    {
        $user = FunctionalUserFactory::create();
        Fixture::insert('cms_ban_users', ['user_id' => $user->id, 'ban_type' => 15, 'ban_time' => time() + 3600, 'ban_while' => time(), 'ban_reason' => 'Spam']);

        $this->postForm($this->url(), ['text' => '<p>Hello</p>'], $user);

        self::assertSame(0, Capsule::table('comments')->where('subject_type', 'library')->count());
    }

    public function testAnArticleWithItsCommentsSwitchedOffTakesNoNewOne(): void
    {
        $closed = $this->article(comments: false);
        $url = '/library/article/' . $closed . '/comments';
        $user = FunctionalUserFactory::create();

        $page = (string) $this->handleRequest($url, cookies: $this->actingAs($user))->getContent();
        $this->postForm($url, ['text' => '<p>Hello</p>'], $user);

        self::assertStringNotContainsString('data-comment-form', $page);
        self::assertSame(0, Capsule::table('comments')->where('subject_id', $closed)->count());
    }

    public function testDeletingTheArticleDeletesItsComments(): void
    {
        $id = $this->comment(FunctionalUserFactory::create()->id);

        $this->handleRequest('/library/article/' . $this->articleId . '/delete?yes', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertFalse(Capsule::table('library_texts')->where('id', $this->articleId)->exists());
        self::assertFalse(Capsule::table('comments')->where('id', $id)->exists());
    }

    public function testAnAnswerSentFromTheFormRecallsTheCommentItAnswers(): void
    {
        $answered = FunctionalUserFactory::create();
        $parent = $this->comment($answered->id);
        $author = FunctionalUserFactory::create();

        $this->postForm($this->url(), ['text' => '<p>An answer</p>', 'reply_to' => $parent], $author);
        $page = (string) $this->handleRequest($this->url(), cookies: $this->actingAs($author))->getContent();

        self::assertSame($parent, (int) Capsule::table('comments')->where('user_id', $author->id)->value('reply_to_id'));
        self::assertStringContainsString('data-comment-reply-to="' . $parent . '"', $page);
        self::assertStringContainsString('href="' . $this->url() . '/comment/' . $parent . '"', $page);
        self::assertStringContainsString('name="reply_to"', $page);
    }

    public function testTheOldCommentsMoveIntoTheCore(): void
    {
        $db = $this->container()->get(ConnectionInterface::class);
        $db->execute(
            'CREATE TEMPORARY TABLE cms_library_comments (id INTEGER NOT NULL PRIMARY KEY, sub_id INTEGER NOT NULL, time INTEGER NOT NULL,'
            . ' user_id INTEGER NOT NULL, text TEXT NOT NULL, reply TEXT NOT NULL, attributes TEXT NOT NULL)'
        );
        try {
            $db->execute(
                'INSERT INTO cms_library_comments (id, sub_id, time, user_id, text, reply, attributes) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [1, $this->articleId, 1_700_000_000, $this->uploaderId, '<p>Old</p>', '<p>Reply</p>', serialize(['author_name' => 'Old', 'reply_id' => $this->uploaderId, 'reply_name' => 'Owner', 'reply_time' => 1_700_000_500])]
            );

            $migration = require ROOT_PATH . 'modules/johncms/library/migrations/2026_11_14_170308_move_comments_to_core.php';
            $migration->bind($this->container()->get(SchemaInterface::class), $db);
            $migration->up();
        } finally {
            $db->execute(Capsule::connection()->getDriverName() === 'sqlite' ? 'DROP TABLE cms_library_comments' : 'DROP TEMPORARY TABLE cms_library_comments');
        }

        self::assertSame(['<p>Old</p>', '<p>Reply</p>'], Capsule::table('comments')->where('subject_type', 'library')->orderBy('created_at')->pluck('text')->all());
        self::assertSame(2, (int) Capsule::table('library_texts')->where('id', $this->articleId)->value('comm_count'));
    }

    private function url(): string
    {
        return '/library/article/' . $this->articleId . '/comments';
    }

    private function article(bool $published = true, bool $comments = true): int
    {
        return Fixture::insert('library_texts', [
            'cat_id'      => Fixture::insert('library_cats', ['name' => 'A shelf']),
            'name'        => 'An article',
            'text'        => 'Body',
            'uploader_id' => $this->uploaderId,
            'premod'      => $published ? 1 : 0,
            'comments'    => $comments ? 1 : 0,
        ]);
    }

    private function comment(int $userId): int
    {
        return Fixture::insert('comments', [
            'subject_type' => 'library',
            'subject_id'   => $this->articleId,
            'user_id'      => $userId,
            'text'         => '<p>A comment</p>',
            'author_name'  => 'Author',
            'created_at'   => gmdate('Y-m-d H:i:s'),
        ]);
    }
}
