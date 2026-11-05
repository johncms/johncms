<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class CollectionFileFieldTest extends FunctionalTestCase
{
    /** @var list<int> */
    private array $files = [];

    protected function tearDown(): void
    {
        $this->container()->get(\Johncms\Files\FileStore::class)->deleteMany($this->files);
        parent::tearDown();
    }

    public function testAdminAndPatchAcceptOnlyAvailableRegisteredFiles(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'file-fields', 'name' => 'Files']);
        $source = Fixture::insert('collection_items', ['collection_id' => $collection, 'code' => 'source', 'name' => 'Source']);
        $store = $this->container()->get(\Johncms\Files\FileStore::class);
        $directory = 'collection-field-tests/' . bin2hex(random_bytes(8));
        $available = $store->storeContents('Available content', 'available.txt', $directory)->id;
        $foreign = $store->storeContents('Foreign content', 'foreign.txt', $directory)->id;
        $this->files = [$available, $foreign];
        Fixture::insert('collection_file_assets', ['file_id' => $available, 'managed' => false]);
        Fixture::insert('collection_file_links', ['file_id' => $available, 'item_id' => $source]);
        $schema = $this->container()->get(CollectionSchemaApiInterface::class);
        $schema->saveField(null, new CollectionFieldFormDTO($collection, 'attachment', 'Attachment', FieldType::File, false, false, 100));
        self::assertSame('registry', $schema->getFields($collection)[0]->settings['file_storage']);
        $user = FunctionalUserFactory::createSupervisor();
        $url = '/admin/collections/' . $collection . '/items';
        self::assertSame(200, $this->postForm($url, ['name' => 'Forged', 'sort' => '100', 'field_attachment' => (string) $foreign], $user)->getStatusCode());
        self::assertSame(1, Capsule::table('collection_items')->count());
        self::assertSame(302, $this->postForm($url, ['name' => 'Assigned', 'code' => 'assigned', 'sort' => '100', 'field_attachment' => (string) $available], $user)->getStatusCode());
        $api = $this->container()->get(CollectionsApiInterface::class);
        $item = $api->getItemByCode($collection, null, 'assigned');
        self::assertSame((string) $available, $item->values['attachment']);
        self::assertSame(1, Capsule::table('collection_file_value_links')->where('item_id', $item->id)->count());
        $api->patchItem($item->id, new CollectionItemPatchDTO(values: ['attachment' => null]));
        self::assertSame(0, Capsule::table('collection_file_value_links')->where('item_id', $item->id)->count());
        self::assertSame(1, Capsule::table('collection_file_links')->where('item_id', $source)->count());
    }

    public function testLegacyPathsAreNotReinterpretedByEditingTheField(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'legacy-files', 'name' => 'Legacy']);
        $field = Fixture::insert('collection_fields', ['collection_id' => $collection, 'code' => 'attachment', 'name' => 'Attachment', 'type' => 'file']);
        $item = Fixture::insert('collection_items', ['collection_id' => $collection, 'code' => 'legacy', 'name' => 'Legacy']);
        Fixture::insert('collection_item_values', ['field_id' => $field, 'item_id' => $item, 'value_string' => '/legacy/photo.jpg']);
        $user = FunctionalUserFactory::createSupervisor();
        $url = '/admin/collections/' . $collection . '/fields';
        $html = (string) $this->handleRequest($url . '/' . $field . '/edit', cookies: $this->actingAs($user))->getContent();
        self::assertSame('legacy', \Dom\HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelector('select[name="file_storage"] option[selected]')->getAttribute('value'));
        self::assertSame(200, $this->postForm($url, ['id' => $field, 'code' => 'attachment', 'name' => 'Attachment', 'type' => 'file', 'sort' => '100', 'file_storage' => 'registry'], $user)->getStatusCode());
        self::assertSame('/legacy/photo.jpg', Capsule::table('collection_item_values')->where('field_id', $field)->value('value_string'));
        self::assertSame(302, $this->postForm($url, ['id' => $field, 'code' => 'attachment', 'name' => 'Attachment renamed', 'type' => 'file', 'sort' => '100', 'file_storage' => 'legacy'], $user)->getStatusCode());
    }
}
