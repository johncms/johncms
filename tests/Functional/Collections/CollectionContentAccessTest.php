<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsPreviewApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsUserApi;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionAccessDeniedException;
use Johncms\Modules\Collections\Application\Services\CollectionWorkspaceMapper;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class CollectionContentAccessTest extends FunctionalTestCase
{
    private int $collection;
    private int $other;
    private int $item;
    private int $foreignItem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = Fixture::insert('collections', ['name' => 'Editorial articles', 'code' => 'editorial', 'settings' => json_encode(['has_sections' => true, '_content_permission' => 'news.manage'])]);
        $this->other = Fixture::insert('collections', ['name' => 'Private collection', 'code' => 'private', 'settings' => json_encode(['has_sections' => true, '_content_permission' => 'collections.manage'])]);
        $this->item = Fixture::insert('collection_items', ['collection_id' => $this->collection, 'code' => 'draft', 'name' => 'Draft']);
        $this->foreignItem = Fixture::insert('collection_items', ['collection_id' => $this->other, 'code' => 'secret', 'name' => 'Secret']);
    }

    public function testEditorCanFillOnlyTheirCollectionAndHasNoSchemaNavigation(): void
    {
        $user = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $base = CollectionWorkspaceMapper::url($this->collection);
        $response = $this->handleRequest($base, cookies: $this->actingAs($user));
        self::assertSame(200, $response->getStatusCode());
        $html = (string) $response->getContent();
        self::assertStringNotContainsString('/admin/collections/' . $this->collection . '/fields', $html);
        self::assertStringNotContainsString('/admin/collections/' . $this->collection . '/settings', $html);
        self::assertMatchesRegularExpression('~href="' . preg_quote($base, '~') . '"[^>]*aria-current="page"~s', $html);
        self::assertSame(200, $this->handleRequest($base . '/new', cookies: $this->actingAs($user))->getStatusCode());
        self::assertSame(200, $this->handleRequest('/admin/collections/' . $this->collection . '/sections', cookies: $this->actingAs($user))->getStatusCode());
        self::assertSame(200, $this->handleRequest($base . '/' . $this->item . '/preview', cookies: $this->actingAs($user))->getStatusCode());
        $response = $this->postForm($base, ['id' => $this->item, 'name' => 'Edited', 'code' => 'draft', 'sort' => '100', 'detail_text' => 'Text'], $user);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('Edited', Capsule::table('collection_items')->where('id', $this->item)->value('name'));
    }

    public function testForgedIdsAndDirectRoutesCannotEscapeTheAuthorizedCollection(): void
    {
        $user = FunctionalUserFactory::createWithPermissions(['news.manage']);
        foreach ([
            '/admin/collections',
            '/admin/collections/' . $this->collection . '/fields',
            '/admin/collections/' . $this->collection . '/settings',
            '/admin/collections/' . $this->collection . '/delete',
            '/admin/collections/' . $this->other . '/items',
            '/admin/collections/' . $this->other . '/sections',
            '/admin/collections/' . $this->other . '/items/' . $this->foreignItem . '/preview',
        ] as $url) {
            self::assertSame(403, $this->handleRequest($url, cookies: $this->actingAs($user))->getStatusCode(), $url);
        }
        $this->postForm('/admin/collections/' . $this->collection . '/items', ['id' => $this->foreignItem, 'name' => 'Forged', 'code' => 'secret', 'sort' => '100'], $user);
        $this->postForm('/admin/collections/' . $this->collection . '/items/' . $this->foreignItem . '/delete', [], $user);
        $this->postForm('/admin/collections', ['id' => $this->collection, 'code' => 'forged', 'name' => 'Forged'], $user);
        self::assertSame('Secret', Capsule::table('collection_items')->where('id', $this->foreignItem)->value('name'));
        self::assertSame('editorial', Capsule::table('collections')->where('id', $this->collection)->value('code'));
    }

    public function testUserApiChecksCollectionAndItemOwnershipWithoutHttp(): void
    {
        $access = $this->createStub(AccessCheckerInterface::class);
        $access->method('allows')->willReturnCallback(static fn (string $permission): bool => $permission === 'news.manage');
        $api = new CollectionsUserApi(
            new EnsureCollectionContentAccessUseCase($access, $this->container()->get(ContentCollectionRepositoryInterface::class)),
            $this->container()->get(CollectionsApiInterface::class),
            $this->container()->get(CollectionSectionsApiInterface::class),
            $this->container()->get(CurrentUser::class),
            $this->container()->get(\Johncms\Modules\Collections\Application\Services\CollectionTagSuggestions::class),
            new \Johncms\Modules\Collections\Application\UseCases\EnsureCollectionRelationAccessUseCase(
                $this->container()->get(\Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface::class),
                new EnsureCollectionContentAccessUseCase($access, $this->container()->get(ContentCollectionRepositoryInterface::class)),
                $this->container()->get(\Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface::class)
            ),
            $this->container()->get(ContentCollectionRepositoryInterface::class),
            new \Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction(),
            new \Johncms\Modules\Collections\Application\UseCases\EnsureCollectionItemWriteAccessUseCase(
                new EnsureCollectionContentAccessUseCase($access, $this->container()->get(ContentCollectionRepositoryInterface::class)),
                $this->container()->get(\Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface::class)
            ),
            $this->container()->get(\Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface::class)
        );
        self::assertNotNull($api->getItem($this->collection, $this->item));
        self::assertNull($api->getItem($this->collection, $this->foreignItem));
        foreach ([
            fn () => $api->getItem($this->other, $this->foreignItem),
            fn () => $api->patchItem($this->collection, $this->foreignItem, new CollectionItemPatchDTO(['name' => 'Forged'])),
            fn () => $api->deleteItem($this->collection, $this->foreignItem),
        ] as $action) {
            try {
                $action();
                self::fail('Foreign content must not be accessible.');
            } catch (CollectionAccessDeniedException) {
                self::assertSame('Secret', Capsule::table('collection_items')->where('id', $this->foreignItem)->value('name'));
            }
        }
    }

    public function testSchemaSettingsPersistContentPermissionButOwnerPermissionCannotBeForged(): void
    {
        $user = FunctionalUserFactory::createSupervisor();
        $form = ['id' => $this->collection, 'code' => 'editorial', 'name' => 'Editorial articles', 'sort' => '100', 'per_page' => '20', 'content_permission' => 'forum.manage'];
        self::assertSame(302, $this->postForm('/admin/collections', $form, $user)->getStatusCode());
        $settings = json_decode(Capsule::table('collections')->where('id', $this->collection)->value('settings'), true);
        self::assertSame('forum.manage', $settings['_content_permission']);
        Capsule::table('collections')->where('id', $this->collection)->update(['owner_module' => 'johncms/news', 'declaration_key' => 'articles']);
        $form['content_permission'] = 'collections.manage';
        self::assertSame(302, $this->postForm('/admin/collections', $form, $user)->getStatusCode());
        $settings = json_decode(Capsule::table('collections')->where('id', $this->collection)->value('settings'), true);
        self::assertSame('forum.manage', $settings['_content_permission']);
    }
}
