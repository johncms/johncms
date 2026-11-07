<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Dom\HTMLDocument;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Files\FileStore;
use Johncms\Http\UploadLimits;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class CollectionFileFieldTest extends FunctionalTestCase
{
    /** @var list<int> */
    private array $files = [];

    protected function tearDown(): void
    {
        $this->container()->get(FileStore::class)->deleteMany($this->files);
        parent::tearDown();
    }

    public function testAdminAndPatchAcceptOnlyAvailableRegisteredFiles(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'file-fields', 'name' => 'Files', 'active' => true, 'public' => true]);
        $source = Fixture::insert('collection_items', ['collection_id' => $collection, 'code' => 'source', 'name' => 'Source']);
        $store = $this->container()->get(FileStore::class);
        $directory = 'collection-field-tests/' . bin2hex(random_bytes(8));
        $available = $store->storeContents('Available content', 'available.txt', $directory)->id;
        $foreign = $store->storeContents('Foreign content', 'foreign.txt', $directory)->id;
        $this->files = [$available, $foreign];
        Fixture::insert('collection_file_assets', ['file_id' => $available, 'managed' => false]);
        Fixture::insert('collection_file_links', ['file_id' => $available, 'item_id' => $source]);
        $schema = $this->container()->get(CollectionSchemaApiInterface::class);
        $schema->saveField(null, new CollectionFieldFormDTO(collectionId: $collection, code: 'attachment', name: 'Attachment', type: FieldType::File, required: false, multiple: false, sort: 100, inList: true));
        $user = FunctionalUserFactory::createSupervisor();
        $url = '/admin/collections/' . $collection . '/items';
        self::assertSame(200, $this->postForm($url, ['name' => 'Forged', 'sort' => '100', 'field_attachment' => (string) $foreign], $user)->getStatusCode());
        self::assertSame(1, Capsule::table('collection_items')->count());
        self::assertSame(302, $this->postForm($url, ['name' => 'Assigned', 'code' => 'assigned', 'sort' => '100', 'active' => '1', 'field_attachment' => (string) $available], $user)->getStatusCode());
        $api = $this->container()->get(CollectionsApiInterface::class);
        $item = $api->getItemByCode($collection, null, 'assigned');
        self::assertSame((string) $available, $item->values['attachment']);
        self::assertSame(1, Capsule::table('collection_file_value_links')->where('item_id', $item->id)->count());
        $panel = $this->handleRequest($url, cookies: $this->actingAs($user));
        $table = HTMLDocument::createFromString((string) $panel->getContent(), LIBXML_NOERROR)->querySelector('.admin-collection-items tbody');
        self::assertStringContainsString('available.txt', $table->textContent);
        $public = $this->handleRequest('/file-fields/assigned.html');
        self::assertSame(200, $public->getStatusCode());
        $document = HTMLDocument::createFromString((string) $public->getContent(), LIBXML_NOERROR);
        self::assertSame('available.txt', $document->querySelector('dd a')->textContent);

        $api->patchItem($item->id, new CollectionItemPatchDTO(values: ['attachment' => null]));
        self::assertSame(0, Capsule::table('collection_file_value_links')->where('item_id', $item->id)->count());
        self::assertSame(1, Capsule::table('collection_file_links')->where('item_id', $source)->count());
    }

    public function testFileLibraryRequiresCollectionAccessAndNeverExposesAnotherEditorsContext(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'library-files', 'name' => 'Files', 'settings' => json_encode(['_content_permission' => 'news.manage'])]);
        $foreign = Fixture::insert('collections', ['code' => 'foreign-library', 'name' => 'Foreign']);
        $source = Fixture::insert('collection_items', ['collection_id' => $collection, 'code' => 'source', 'name' => 'Source']);
        $otherSource = Fixture::insert('collection_items', ['collection_id' => $foreign, 'code' => 'secret', 'name' => 'Secret']);
        $file = $this->storedFile('Visible contents', 'visible.txt');
        $secret = $this->storedFile('Secret contents', 'private.txt');
        foreach ([[$source, $file], [$otherSource, $secret]] as [$item, $id]) {
            Fixture::insert('collection_file_assets', ['file_id' => $id, 'managed' => false]);
            Fixture::insert('collection_file_links', ['file_id' => $id, 'item_id' => $item]);
        }
        $this->container()->get(CollectionSchemaApiInterface::class)->saveField(null, new CollectionFieldFormDTO($collection, 'attachment', 'Attachment', FieldType::File, false, false, 100));
        $user = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $other = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $base = '/admin/collections/' . $collection;
        $response = $this->handleRequest($base . '/files/attachment?q=visible', cookies: $this->actingAs($user));
        self::assertSame(200, $response->getStatusCode());
        self::assertSame([(string) $file], array_column(json_decode((string) $response->getContent(), true)['files'], 'code'));
        self::assertStringNotContainsString('private.txt', (string) $response->getContent());
        $context = $this->postForm($base . '/uploads/context', [], $user);
        $token = json_decode((string) $context->getContent(), true)['context'];
        self::assertSame(422, $this->handleRequest($base . '/files/attachment?context=' . $token, cookies: $this->actingAs($other))->getStatusCode());
        self::assertSame(403, $this->handleRequest('/admin/collections/' . $foreign . '/files/attachment', cookies: $this->actingAs($user))->getStatusCode());
    }

    public function testUploadedFilesAreSavedInTheChosenOrder(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'ordered-files', 'name' => 'Ordered files']);
        $this->container()->get(CollectionSchemaApiInterface::class)->saveField(null, new CollectionFieldFormDTO($collection, 'attachment', 'Attachments', FieldType::File, false, true, 100));
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $collection;
        $token = json_decode((string) $this->postForm($base . '/uploads/context', [], $user)->getContent(), true)['context'];
        $chosen = [];
        foreach (['first.txt' => 'First file', 'second.txt' => 'Second file'] as $name => $contents) {
            $path = tempnam(sys_get_temp_dir(), 'ordered-file-');
            file_put_contents($path, $contents);
            try {
                $response = $this->handleRequest($base . '/uploads?field=attachment&context=' . $token, 'POST', $this->withCsrfToken(), cookies: $this->actingAs($user), files: [
                    'upload' => new UploadedFile($path, $name, 'text/plain', null, true),
                ]);
            } finally {
                unlink($path);
            }
            $chosen[] = (string) json_decode((string) $response->getContent(), true)['id'];
        }
        $this->files = [...$this->files, ...array_map(intval(...), $chosen)];
        $chosen = array_reverse($chosen);
        self::assertSame(302, $this->postForm($base . '/items', ['name' => 'Saved', 'code' => 'saved', 'sort' => '100', 'upload_context' => $token, 'field_attachment' => $chosen], $user)->getStatusCode());
        self::assertSame($chosen, $this->container()->get(CollectionsApiInterface::class)->getItemByCode($collection, null, 'saved')->values['attachment']);
        self::assertSame(0, Capsule::table('collection_file_links')->count());
        self::assertSame(2, Capsule::table('collection_file_value_links')->count());
        $document = HTMLDocument::createFromString((string) $this->handleRequest($base . '/items/new', cookies: $this->actingAs($user))->getContent(), LIBXML_NOERROR);
        self::assertNotNull($document->querySelector('dialog[data-file-picker]'));
        self::assertNull($document->querySelector('[name^="file_uploads"]'), 'Files go up through the script alone.');
    }

    private function storedFile(string $contents, string $name): int
    {
        $file = $this->container()->get(FileStore::class)->storeContents($contents, $name, 'file-widget-tests/' . bin2hex(random_bytes(8)));
        $this->files[] = $file->id;
        return $file->id;
    }
}
