<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use PDO;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;

/**
 * The four pages served by the legacy Johncms\Comments class. They are its only callers and the
 * only place its display parameters — the sub-action ('reply', 'edit', 'del') and the page offset
 * — are handed over, so nothing else covers that hand-off.
 *
 * What is asserted and why it is not more: the suite drives requests as a guest and creates no
 * fixtures, while each of these pages sits behind its own precondition — the file must exist on
 * disk, the article must have passed pre-moderation, the album must be public. On a stand that
 * does not satisfy them the page answers 403/404 before Comments is ever constructed. So the
 * check that always holds is "never a server error", and the full-page assertions run only when
 * the stand does let the page through. A broken hand-off to Comments surfaces as a 500 here.
 */
final class CommentsPageTest extends FunctionalTestCase
{
    public function testFileCommentsPage(): void
    {
        $this->assertCommentsPageIsServed('/downloads/comments/%d', 'download__files');
    }

    public function testPhotoCommentsPage(): void
    {
        $this->assertCommentsPageIsServed('/album/photo/%d/comments', 'cms_album_files');
    }

    public function testArticleCommentsPage(): void
    {
        $this->assertCommentsPageIsServed('/library/article/%d/comments', 'library_texts');
    }

    public function testProfileGuestbookPage(): void
    {
        $this->assertCommentsPageIsServed('/profile/%d/guestbook', 'users');
    }

    private function assertCommentsPageIsServed(string $uriTemplate, string $table): void
    {
        $uri = sprintf($uriTemplate, $this->firstId($table));

        $response = $this->handleRequest($uri);
        $status = $response->getStatusCode();

        self::assertLessThan(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $status,
            sprintf('%s answered %d', $uri, $status)
        );

        if ($status !== Response::HTTP_OK) {
            return;
        }

        $content = (string) $response->getContent();
        self::assertStringContainsString('<html', $content, $uri . ' did not render a page');
        self::assertStringContainsString('</html>', $content, $uri . ' rendered a truncated page');
    }

    private function firstId(string $table): int
    {
        $id = $this->container()->get(PDO::class)
            ->query('SELECT `id` FROM `' . $table . '` ORDER BY `id` LIMIT 1')
            ->fetchColumn();

        if ($id === false) {
            self::markTestSkipped(sprintf('The stand has no rows in `%s`.', $table));
        }

        return (int) $id;
    }
}
