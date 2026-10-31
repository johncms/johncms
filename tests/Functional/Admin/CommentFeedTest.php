<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Dom\HTMLDocument;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The comments of every module in one list on the panel, the newest first, each leading to its
 * place on the site — and only those of the sections the visitor moderates.
 *
 * A comment is told by where it leads: the panel answers in its own language.
 */
final class CommentFeedTest extends FunctionalTestCase
{
    public function testTheCommentsOfEveryModuleAreListedTheNewestFirst(): void
    {
        $this->commentEverywhere();

        $page = $this->pageOf('/admin/comments', FunctionalUserFactory::createSupervisor());

        self::assertSame(
            ['/news/opening.html#article-comments', '/album/photo/3/comments', '/library/article/2/comments', '/downloads/comments/1'],
            $this->listed($page)
        );
    }

    public function testTheTextAndTheAuthorAreShown(): void
    {
        $author = FunctionalUserFactory::create(['name' => 'Frodo']);
        Fixture::insert('cms_library_comments', [
            'sub_id'     => 2,
            'user_id'    => $author->id,
            'time'       => time(),
            'text'       => '<p>A <b>fine</b> article</p>',
            'attributes' => serialize(['author_name' => 'Frodo']),
        ]);

        $page = $this->pageOf('/admin/comments', FunctionalUserFactory::createSupervisor());

        self::assertSame('A fine article', trim((string) $page->querySelector('.admin-comment__text')?->textContent));
        self::assertSame('/admin/users/' . $author->id, $page->querySelector('.admin-comment__author')?->getAttribute('href'));
    }

    public function testAModeratorOfOneSectionSeesTheCommentsOfThatSectionAlone(): void
    {
        $this->commentEverywhere();

        $moderator = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, LibraryPermissions::MODERATE]);

        self::assertSame(['/library/article/2/comments'], $this->listed($this->pageOf('/admin/comments', $moderator)));
    }

    public function testTheDashboardShowsTheLatestComments(): void
    {
        $this->commentEverywhere();

        $page = $this->pageOf('/admin', FunctionalUserFactory::createSupervisor());

        self::assertSame(
            ['/news/opening.html#article-comments', '/album/photo/3/comments', '/library/article/2/comments', '/downloads/comments/1'],
            $this->listed($page)
        );
        self::assertNotNull($page->querySelector('a[href="/admin/comments"]'));
    }

    /**
     * One comment in each module, an hour apart: the news the newest, the downloads the oldest.
     */
    private function commentEverywhere(): void
    {
        $now = time();
        $legacy = static fn (int $subject, int $time): array => [
            'sub_id'     => $subject,
            'user_id'    => 0,
            'time'       => $time,
            'text'       => 'A comment',
            'attributes' => serialize(['author_name' => 'Guest']),
        ];

        Fixture::insert('download__comments', $legacy(1, $now - 4 * 3600));
        Fixture::insert('cms_library_comments', $legacy(2, $now - 3 * 3600));
        Fixture::insert('cms_album_comments', $legacy(3, $now - 2 * 3600));
        $article = Fixture::insert('news_articles', ['name' => 'Opening', 'code' => 'opening']);
        Fixture::insert('news_comments', [
            'article_id' => $article,
            'user_id'    => 0,
            'text'       => 'A comment',
            'created_at' => date('Y-m-d H:i:s', $now - 3600),
        ]);
    }

    private function pageOf(string $path, User $user): HTMLDocument
    {
        $response = $this->handleRequest($path, cookies: $this->actingAs($user));

        return HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    }

    /**
     * @return list<string> Where the comments of the page lead, in their order.
     */
    private function listed(HTMLDocument $page): array
    {
        $links = [];

        foreach ($page->querySelectorAll('.admin-comment__subject') as $link) {
            $links[] = (string) $link->getAttribute('href');
        }

        return $links;
    }
}
