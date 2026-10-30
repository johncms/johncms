<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The collections in the panel: the list of them, and one collection as a page with a tab for its
 * items, sections, fields and settings.
 */
final class CollectionsAdminWorkspaceTest extends FunctionalTestCase
{
    public function testTheListCountsWhatEveryCollectionHolds(): void
    {
        $blog = $this->collection('blog', 'Blog');
        Fixture::insert('collection_fields', ['collection_id' => $blog, 'code' => 'author', 'name' => 'Author', 'type' => 'string', 'required' => 0, 'multiple' => 0, 'sort' => 100]);
        foreach (['first', 'second'] as $code) {
            Fixture::insert('collection_items', ['collection_id' => $blog, 'name' => ucfirst($code), 'code' => $code, 'active' => 1, 'sort' => 100]);
        }

        $content = $this->page('/admin/collections');

        self::assertStringContainsString('href="/admin/collections/' . $blog . '/items">Blog</a>', $content);
        self::assertMatchesRegularExpression('#admin-collections-table__number[^"]*">2</td>#', $content);
        // The actions of a row: the parts of the collection and its deleting in the dialog.
        self::assertStringContainsString('/admin/collections/' . $blog . '/settings', $content);
        self::assertMatchesRegularExpression('#href="/admin/collections/' . $blog . '/delete"\s*data-admin-modal#', $content);
    }

    public function testEveryPartOfACollectionIsATabOfIt(): void
    {
        $blog = $this->collection('blog', 'Blog');

        foreach (['items', 'sections', 'fields', 'settings'] as $tab) {
            $content = $this->page('/admin/collections/' . $blog . '/' . $tab);

            self::assertStringContainsString('<h1 class="admin-page__title">Blog</h1>', $content, $tab);
            self::assertMatchesRegularExpression('#href="/admin/collections/' . $blog . '/' . $tab . '"\s*aria-current="page"#', $content, $tab);
        }
    }

    public function testTheSectionsTabIsLeftOutWhenTheCollectionHasNone(): void
    {
        $cities = $this->collection('cities', 'Cities', hasSections: false);

        $content = $this->page('/admin/collections/' . $cities . '/items');

        self::assertStringNotContainsString('/admin/collections/' . $cities . '/sections"', $content);
    }

    public function testTheOldAddressOfTheSettingsIsGone(): void
    {
        $blog = $this->collection('blog', 'Blog');

        $response = $this->handleRequest('/admin/collections/' . $blog . '/edit', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testANewCollectionOpensOnItsFields(): void
    {
        $response = $this->handleRequest(
            '/admin/collections',
            'POST',
            $this->withCsrfToken(['name' => 'Products', 'code' => 'products', 'active' => '1', 'public' => '1', 'per_page' => '10', 'sort' => '100']),
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        $id = ContentCollection::query()->where('code', 'products')->value('id');
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/collections/' . $id . '/fields', $response->headers->get('Location'));
    }

    public function testSavedSettingsStayOnTheirTab(): void
    {
        $blog = $this->collection('blog', 'Blog');

        $response = $this->handleRequest(
            '/admin/collections',
            'POST',
            $this->withCsrfToken(['id' => (string) $blog, 'name' => 'Journal', 'code' => 'blog', 'active' => '1', 'per_page' => '10', 'sort' => '100']),
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertSame('/admin/collections/' . $blog . '/settings', $response->headers->get('Location'));
        self::assertSame('Journal', ContentCollection::query()->whereKey($blog)->value('name'));
    }

    public function testDeletingACollectionAsksInTheDialogWithWhatGoesWithIt(): void
    {
        $blog = $this->collection('blog', 'Blog');
        Fixture::insert('collection_items', ['collection_id' => $blog, 'name' => 'Post', 'code' => 'post', 'active' => 1, 'sort' => 100]);
        $cookies = $this->actingAs(FunctionalUserFactory::createSupervisor());

        $dialog = (string) $this->handleRequest(
            '/admin/collections/' . $blog . '/delete',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $cookies
        )->getContent();
        $page = (string) $this->handleRequest('/admin/collections/' . $blog . '/delete', cookies: $cookies)->getContent();

        self::assertStringStartsWith('<form class="modal-content" method="post" action="/admin/collections/' . $blog . '/delete">', trim($dialog));
        self::assertStringContainsString('Blog', $dialog);
        self::assertStringContainsString('class="modal admin-dialog-page"', $page);
    }

    private function collection(string $code, string $name, bool $hasSections = true): int
    {
        return Fixture::insert('collections', [
            'code'     => $code,
            'name'     => $name,
            'settings' => json_encode(['has_sections' => $hasSections, 'per_page' => 10]),
            'sort'     => 100,
            'active'   => 1,
            'public'   => 1,
        ]);
    }

    private function page(string $uri): string
    {
        $response = $this->handleRequest($uri, cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $uri);

        return (string) $response->getContent();
    }
}
