<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Dom\HTMLDocument;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionsUserApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionAccessDeniedException;
use Johncms\Modules\Collections\Application\Services\CollectionsPermissions as Permissions;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class CollectionActionPermissionsTest extends FunctionalTestCase
{
    private int $collection;
    private int $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = Fixture::insert('collections', ['code' => 'rights', 'name' => 'Rights', 'settings' => json_encode([
            'has_sections' => true, '_content_permission' => 'news.manage', 'content_actions' => [
                'create' => Permissions::CREATE, 'edit' => Permissions::EDIT, 'publish' => Permissions::PUBLISH,
                'delete' => Permissions::DELETE, 'sections' => Permissions::SECTIONS,
            ],
        ], JSON_THROW_ON_ERROR)]);
        $this->item = Fixture::insert('collection_items', ['collection_id' => $this->collection, 'code' => 'draft', 'name' => 'Draft', 'active' => 0]);
    }

    public function testEditorCanSaveTextButCannotPublishOrCreateOrDeleteThroughHttpOrApi(): void
    {
        $user = FunctionalUserFactory::createWithPermissions(['news.manage', Permissions::EDIT]);
        $base = '/admin/collections/' . $this->collection . '/items';
        $html = (string) $this->handleRequest($base . '/' . $this->item . '/edit', cookies: $this->actingAs($user))->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        self::assertNull($document->querySelector('input[name="active"]'));
        self::assertNull($document->querySelector('input[name="active_from"]'));
        $form = ['id' => $this->item, 'name' => 'Edited', 'code' => 'draft', 'sort' => '100'];
        self::assertSame(302, $this->postForm($base, $form, $user)->getStatusCode());
        self::assertSame('Edited', Capsule::table('collection_items')->where('id', $this->item)->value('name'));
        foreach ([['active' => '1'], ['active_from' => '2026-01-01T12:00'], ['active_to' => '2027-01-01T12:00']] as $forged) {
            self::assertSame(403, $this->postForm($base, $form + $forged, $user)->getStatusCode());
        }
        self::assertSame(403, $this->handleRequest($base . '/new', cookies: $this->actingAs($user))->getStatusCode());
        self::assertSame(403, $this->postForm($base . '/' . $this->item . '/delete', [], $user)->getStatusCode());
        $api = $this->container()->get(CollectionsUserApiInterface::class);
        $api->patchItem($this->collection, $this->item, new CollectionItemPatchDTO(['name' => 'API edit']));
        foreach ([
            fn () => $api->patchItem($this->collection, $this->item, new CollectionItemPatchDTO(['active' => true])),
            fn () => $api->updateItem($this->item, new CollectionItemFormDTO($this->collection, null, 'draft', 'Forged', true, null, null, 100, null, null, [])),
            fn () => $api->deleteItem($this->collection, $this->item),
            fn () => $api->addItem(new CollectionItemFormDTO($this->collection, null, 'new', 'New', false, null, null, 100, null, null, [])),
        ] as $operation) {
            try {
                $operation();
                self::fail('The user API must enforce action permissions.');
            } catch (CollectionAccessDeniedException) {
                self::assertSame(0, Capsule::table('collection_items')->where('id', $this->item)->value('active'));
            }
        }
    }

    public function testCreatorWithoutPublishingPermissionCreatesDraftsAndReadOnlyUsersSeeNoWriteActions(): void
    {
        $user = FunctionalUserFactory::createWithPermissions(['news.manage', Permissions::CREATE]);
        $base = '/admin/collections/' . $this->collection . '/items';
        self::assertSame(302, $this->postForm($base, ['name' => 'New draft', 'sort' => '100'], $user)->getStatusCode());
        self::assertSame(0, Capsule::table('collection_items')->where('name', 'New draft')->value('active'));
        self::assertSame(403, $this->postForm($base, ['name' => 'Forbidden publication', 'sort' => '100', 'active' => '1'], $user)->getStatusCode());
        $reader = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $html = (string) $this->handleRequest($base, cookies: $this->actingAs($reader))->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        self::assertNull($document->querySelector('a[href*="/items/new"]'));
        self::assertNull($document->querySelector('a[href*="/edit?"]'));
        self::assertNull($document->querySelector('a[href*="/delete?"]'));
        self::assertSame(403, $this->handleRequest('/admin/collections/' . $this->collection . '/sections', cookies: $this->actingAs($reader))->getStatusCode());
        self::assertSame(403, $this->postForm('/admin/collections/' . $this->collection . '/uploads/context', [], $reader)->getStatusCode());
    }

    public function testPublishedTextEditsPreservePublicationSecondsButMovingRequiresPublishing(): void
    {
        Capsule::table('collection_items')->where('id', $this->item)->update(['active' => 1, 'active_from' => '2020-01-01 12:34:56', 'active_to' => '2030-01-01 12:34:57']);
        $section = Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'name' => 'Target', 'code' => 'target', 'active' => 1]);
        $user = FunctionalUserFactory::createWithPermissions(['news.manage', Permissions::EDIT]);
        $base = '/admin/collections/' . $this->collection . '/items';
        self::assertSame(302, $this->postForm($base, ['id' => $this->item, 'name' => 'Edited publication', 'code' => 'draft', 'sort' => '100'], $user)->getStatusCode());
        $row = Capsule::table('collection_items')->where('id', $this->item)->first();
        self::assertSame(1, $row->active);
        self::assertSame('2020-01-01 12:34:56', $row->active_from);
        self::assertSame('2030-01-01 12:34:57', $row->active_to);
        $this->expectException(CollectionAccessDeniedException::class);
        $this->container()->get(CollectionsUserApiInterface::class)->patchItem($this->collection, $this->item, new CollectionItemPatchDTO(['sectionId' => $section]));
    }

    public function testActionSettingsValidateAndLegacySettingsPostsPreserveThePolicy(): void
    {
        $supervisor = FunctionalUserFactory::createSupervisor();
        $form = ['id' => $this->collection, 'code' => 'rights', 'name' => 'Rights', 'sort' => '100', 'per_page' => '20', 'content_permission' => 'news.manage'];
        $before = Capsule::table('collections')->where('id', $this->collection)->value('settings');
        self::assertSame(200, $this->postForm('/admin/collections', $form + ['action_permissions' => ['unknown' => Permissions::EDIT]], $supervisor)->getStatusCode());
        self::assertSame($before, Capsule::table('collections')->where('id', $this->collection)->value('settings'));
        self::assertSame(302, $this->postForm('/admin/collections', $form, $supervisor)->getStatusCode());
        $settings = json_decode(json: Capsule::table('collections')->where('id', $this->collection)->value('settings'), associative: true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(Permissions::PUBLISH, $settings['content_actions']['publish']);
    }

    public function testSectionManagerCannotActivateOrMoveActiveSectionsWithoutPublishingPermission(): void
    {
        $section = Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'name' => 'Visible', 'code' => 'visible', 'active' => 1]);
        $user = FunctionalUserFactory::createWithPermissions(['news.manage', Permissions::SECTIONS]);
        $base = '/admin/collections/' . $this->collection . '/sections';
        self::assertSame(302, $this->postForm($base, ['id' => $section, 'name' => 'Renamed', 'code' => 'visible', 'sort' => '100'], $user)->getStatusCode());
        self::assertSame(1, Capsule::table('collection_sections')->where('id', $section)->value('active'));
        $api = $this->container()->get(CollectionsUserApiInterface::class);
        $draft = $api->addSection(new CollectionSectionFormDTO($this->collection, null, 'draft', 'Draft section', null, false, 100));
        try {
            $api->updateSection($draft, new CollectionSectionFormDTO($this->collection, null, 'draft', 'Draft section', null, true, 100));
            self::fail('Activating a section requires publication permission.');
        } catch (CollectionAccessDeniedException) {
            self::assertSame(0, Capsule::table('collection_sections')->where('id', $draft)->value('active'));
        }
        $this->expectException(CollectionAccessDeniedException::class);
        $api->updateSection($section, new CollectionSectionFormDTO($this->collection, $draft, 'visible', 'Visible', null, true, 100));
    }
}
