<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The four pages served by the legacy Johncms\Comments class. They are its only callers and the
 * only place its display parameters — the sub-action ('reply', 'edit', 'del') and the page offset
 * — are handed over, so nothing else covers that hand-off.
 *
 * Each page sits behind a precondition of its own: three of them want a signed-in visitor, and
 * the file of a download has to exist on disk. The test creates what each one asks for, so the
 * page is really rendered and a broken hand-off to Comments cannot hide behind a 403 or a 404.
 */
final class CommentsPageTest extends FunctionalTestCase
{
    /** @var string|null The file a download points at; removed again in tearDown(). */
    private ?string $storedFile = null;

    protected function tearDown(): void
    {
        if ($this->storedFile !== null) {
            unlink($this->storedFile);
            rmdir(dirname($this->storedFile));
            $this->storedFile = null;
        }

        parent::tearDown();
    }

    public function testFileCommentsPage(): void
    {
        $directory = sys_get_temp_dir() . '/johncms-functional-' . bin2hex(random_bytes(4));
        mkdir($directory);
        $this->storedFile = $directory . '/file.zip';
        file_put_contents($this->storedFile, 'a download');

        $fileId = Fixture::insert(
            'download__files',
            // type 2 is a file of the catalogue; 0 and 1 are the categories.
            ['type' => 2, 'name' => 'file.zip', 'dir' => $directory, 'rus_name' => 'A file']
        );

        $this->assertCommentsPageIsRendered('/downloads/comments/' . $fileId);
    }

    public function testPhotoCommentsPage(): void
    {
        $user = FunctionalUserFactory::create();
        $albumId = Fixture::insert('cms_album_cat', ['user_id' => $user->id, 'name' => 'An album', 'access' => 3]);
        $photoId = Fixture::insert(
            'cms_album_files',
            ['user_id' => $user->id, 'album_id' => $albumId, 'img_name' => 'photo.jpg', 'access' => 3]
        );

        $this->assertCommentsPageIsRendered(
            '/album/photo/' . $photoId . '/comments',
            $this->actingAs($user)
        );
    }

    public function testArticleCommentsPage(): void
    {
        $categoryId = Fixture::insert('library_cats', ['name' => 'A shelf']);
        $articleId = Fixture::insert(
            'library_texts',
            ['cat_id' => $categoryId, 'name' => 'An article', 'text' => 'Body']
        );

        $this->assertCommentsPageIsRendered(
            '/library/article/' . $articleId . '/comments',
            $this->actingAs(FunctionalUserFactory::create())
        );
    }

    /**
     * The guestbook of a profile answers a guest with a 404 — AuthorizedUserMiddleware — so this
     * one is opened by a signed-in visitor as well.
     */
    public function testProfileGuestbookPage(): void
    {
        $user = FunctionalUserFactory::create();

        $this->assertCommentsPageIsRendered('/profile/' . $user->id . '/guestbook', $this->actingAs($user));
    }

    /**
     * @param array<string, string> $cookies
     */
    private function assertCommentsPageIsRendered(string $uri, array $cookies = []): void
    {
        $response = $this->handleRequest($uri, cookies: $cookies);

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
            sprintf('%s answered %d', $uri, $response->getStatusCode())
        );

        $content = (string) $response->getContent();
        self::assertStringContainsString('<html', $content, $uri . ' did not render a page');
        self::assertStringContainsString('</html>', $content, $uri . ' rendered a truncated page');
    }
}
