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
 * Deleting through the legacy Johncms\Comments class, on the comments of a photo of an album.
 *
 * Only a POST that carries the CSRF token and the confirmed field of the dialog deletes. A link,
 * the old one with &yes included, and a form sent without the answer change nothing.
 */
final class LegacyCommentsDeleteTest extends FunctionalTestCase
{
    private int $photoId;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();

        $owner = FunctionalUserFactory::create();
        $albumId = Fixture::insert('cms_album_cat', ['user_id' => $owner->id, 'name' => 'An album', 'access' => 3]);
        $this->photoId = Fixture::insert('cms_album_files', ['user_id' => $owner->id, 'album_id' => $albumId, 'img_name' => 'photo.jpg', 'access' => 3]);
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
        self::assertSame(1, $this->photoCommentCount());
    }

    public function testConfirmedPostWithAllDeletesEveryCommentOfTheAuthorUnderThePhoto(): void
    {
        $authorId = FunctionalUserFactory::create()->id;
        $first = $this->insertComment($authorId);
        $second = $this->insertComment($authorId);
        $ofSomeoneElse = $this->insertComment(FunctionalUserFactory::create()->id);
        $otherPhotoId = Fixture::insert('cms_album_files', ['user_id' => $authorId, 'album_id' => 0, 'img_name' => 'other.jpg', 'access' => 3]);
        $underOtherPhoto = $this->insertComment($authorId, $otherPhotoId);

        $this->postForm(
            $this->url() . '?mod=del&item=' . $first,
            ['confirmed' => '1', 'all' => '1'],
            $this->moderator
        );

        self::assertFalse($this->commentExists($first));
        self::assertFalse($this->commentExists($second));
        self::assertTrue($this->commentExists($ofSomeoneElse));
        self::assertTrue($this->commentExists($underOtherPhoto));
        self::assertSame(1, $this->photoCommentCount());
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
        return '/album/photo/' . $this->photoId . '/comments';
    }

    private function insertComment(int $userId, ?int $photoId = null): int
    {
        return Fixture::insert('cms_album_comments', [
            'sub_id'     => $photoId ?? $this->photoId,
            'user_id'    => $userId,
            'time'       => time(),
            'text'       => 'A comment',
            'reply'      => '',
            'attributes' => serialize(['author_name' => 'Author', 'author_ip' => '127.0.0.1', 'author_browser' => 'Test']),
        ]);
    }

    private function commentExists(int $id): bool
    {
        return Capsule::table('cms_album_comments')->where('id', $id)->exists();
    }

    private function photoCommentCount(): int
    {
        return (int) Capsule::table('cms_album_files')->where('id', $this->photoId)->value('comm_count');
    }
}
