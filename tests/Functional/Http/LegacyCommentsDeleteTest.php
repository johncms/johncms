<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Deleting through the legacy Johncms\Comments class, on the comments of an article of the library.
 *
 * Only a POST that carries the CSRF token and the confirmed field of the dialog deletes. A link,
 * the old one with &yes included, and a form sent without the answer change nothing.
 */
final class LegacyCommentsDeleteTest extends FunctionalTestCase
{
    private int $articleId;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();

        $categoryId = Fixture::insert('library_cats', ['name' => 'A shelf']);
        $this->articleId = Fixture::insert('library_texts', ['cat_id' => $categoryId, 'name' => 'An article', 'text' => 'Body']);
        $this->moderator = FunctionalUserFactory::createSupervisor();
    }

    public function testOldLinkWithYesDeletesNothing(): void
    {
        $commentId = $this->insertComment(FunctionalUserFactory::create()->id);

        $response = $this->handleRequest(
            $this->url() . '?mod=del&item=' . $commentId . '&yes&all',
            cookies: $this->actingAs($this->moderator)
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertTrue($this->commentExists($commentId));
    }

    public function testPostWithoutCsrfTokenIsRefused(): void
    {
        $commentId = $this->insertComment(FunctionalUserFactory::create()->id);

        $response = $this->handleRequest(
            $this->url() . '?mod=del&item=' . $commentId,
            'POST',
            ['confirmed' => '1'],
            cookies: $this->actingAs($this->moderator)
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertTrue($this->commentExists($commentId));
    }

    public function testPostWithoutConfirmationDeletesNothing(): void
    {
        $commentId = $this->insertComment(FunctionalUserFactory::create()->id);

        $response = $this->postForm($this->url() . '?mod=del&item=' . $commentId, [], $this->moderator);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertTrue($this->commentExists($commentId));
    }

    public function testConfirmedPostDeletesTheComment(): void
    {
        $authorId = FunctionalUserFactory::create()->id;
        $deleted = $this->insertComment($authorId);
        $kept = $this->insertComment($authorId);

        $response = $this->postForm(
            $this->url() . '?mod=del&item=' . $deleted,
            ['confirmed' => '1'],
            $this->moderator
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertFalse($this->commentExists($deleted));
        self::assertTrue($this->commentExists($kept));
        self::assertSame(1, $this->articleCommentCount());
    }

    public function testConfirmedPostWithAllDeletesEveryCommentOfTheAuthorUnderTheArticle(): void
    {
        $authorId = FunctionalUserFactory::create()->id;
        $first = $this->insertComment($authorId);
        $second = $this->insertComment($authorId);
        $ofSomeoneElse = $this->insertComment(FunctionalUserFactory::create()->id);
        $otherArticleId = Fixture::insert('library_texts', ['cat_id' => 0, 'name' => 'Other', 'text' => 'Body']);
        $underOtherArticle = $this->insertComment($authorId, $otherArticleId);

        $this->postForm(
            $this->url() . '?mod=del&item=' . $first,
            ['confirmed' => '1', 'all' => '1'],
            $this->moderator
        );

        self::assertFalse($this->commentExists($first));
        self::assertFalse($this->commentExists($second));
        self::assertTrue($this->commentExists($ofSomeoneElse));
        self::assertTrue($this->commentExists($underOtherArticle));
        self::assertSame(1, $this->articleCommentCount());
    }

    public function testListOffersDeletionOnlyAsConfirmedForms(): void
    {
        $commentId = $this->insertComment(FunctionalUserFactory::create()->id);

        $content = (string) $this->handleRequest($this->url(), cookies: $this->actingAs($this->moderator))->getContent();

        self::assertMatchesRegularExpression(
            '~<form method="post" action="[^"]*mod=del&amp;item=' . $commentId . '" data-confirm=~',
            $content
        );
        self::assertStringNotContainsString('&amp;yes', $content);
    }

    private function url(): string
    {
        return '/library/article/' . $this->articleId . '/comments';
    }

    private function insertComment(int $userId, ?int $articleId = null): int
    {
        return Fixture::insert('cms_library_comments', [
            'sub_id'     => $articleId ?? $this->articleId,
            'user_id'    => $userId,
            'time'       => time(),
            'text'       => 'A comment',
            'reply'      => '',
            'attributes' => serialize(['author_name' => 'Author', 'author_ip' => '127.0.0.1', 'author_browser' => 'Test']),
        ]);
    }

    private function commentExists(int $id): bool
    {
        return Capsule::table('cms_library_comments')->where('id', $id)->exists();
    }

    private function articleCommentCount(): int
    {
        return (int) Capsule::table('library_texts')->where('id', $this->articleId)->value('comm_count');
    }
}
