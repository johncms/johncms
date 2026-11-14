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
 * Deleting through the legacy Johncms\Comments class, on the comments of a download.
 *
 * Only a POST that carries the CSRF token and the confirmed field of the dialog deletes. A link,
 * the old one with &yes included, and a form sent without the answer change nothing.
 */
final class LegacyCommentsDeleteTest extends FunctionalTestCase
{
    /** @var string|null The file a download points at; removed again in tearDown(). */
    private ?string $storedFile = null;

    private int $fileId;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();

        $directory = sys_get_temp_dir() . '/johncms-functional-' . bin2hex(random_bytes(4));
        mkdir($directory);
        $this->storedFile = $directory . '/file.zip';
        file_put_contents($this->storedFile, 'a download');

        // type 2 is a file of the catalogue; 0 and 1 are the categories.
        $this->fileId = Fixture::insert(
            'download__files',
            ['type' => 2, 'name' => 'file.zip', 'dir' => $directory, 'rus_name' => 'A file']
        );
        $this->moderator = FunctionalUserFactory::createSupervisor();
    }

    protected function tearDown(): void
    {
        if ($this->storedFile !== null) {
            unlink($this->storedFile);
            rmdir(dirname($this->storedFile));
            $this->storedFile = null;
        }

        parent::tearDown();
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
        self::assertSame(1, $this->fileCommentCount());
    }

    public function testConfirmedPostWithAllDeletesEveryCommentOfTheAuthorUnderTheFile(): void
    {
        $authorId = FunctionalUserFactory::create()->id;
        $first = $this->insertComment($authorId);
        $second = $this->insertComment($authorId);
        $ofSomeoneElse = $this->insertComment(FunctionalUserFactory::create()->id);
        $otherFileId = Fixture::insert('download__files', ['type' => 2, 'name' => 'other.zip', 'rus_name' => 'Other']);
        $underOtherFile = $this->insertComment($authorId, $otherFileId);

        $this->postForm(
            $this->url() . '?mod=del&item=' . $first,
            ['confirmed' => '1', 'all' => '1'],
            $this->moderator
        );

        self::assertFalse($this->commentExists($first));
        self::assertFalse($this->commentExists($second));
        self::assertTrue($this->commentExists($ofSomeoneElse));
        self::assertTrue($this->commentExists($underOtherFile));
        self::assertSame(1, $this->fileCommentCount());
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
        return '/downloads/comments/' . $this->fileId;
    }

    private function insertComment(int $userId, ?int $fileId = null): int
    {
        return Fixture::insert('download__comments', [
            'sub_id'     => $fileId ?? $this->fileId,
            'user_id'    => $userId,
            'time'       => time(),
            'text'       => 'A comment',
            'reply'      => '',
            'attributes' => serialize(['author_name' => 'Author', 'author_ip' => '127.0.0.1', 'author_browser' => 'Test']),
        ]);
    }

    private function commentExists(int $id): bool
    {
        return Capsule::table('download__comments')->where('id', $id)->exists();
    }

    private function fileCommentCount(): int
    {
        return (int) Capsule::table('download__files')->where('id', $this->fileId)->value('comm_count');
    }
}
