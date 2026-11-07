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

    public function testNativeMultipleUploadsSurviveSearchAndSaveTheirChosenOrder(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'native-files', 'name' => 'Native files']);
        $this->container()->get(CollectionSchemaApiInterface::class)->saveField(null, new CollectionFieldFormDTO($collection, 'attachment', 'Attachments', FieldType::File, false, true, 100));
        $this->container()->get(CollectionSchemaApiInterface::class)->saveField(null, new CollectionFieldFormDTO($collection, 'cover', 'Cover', FieldType::File, false, false, 100));
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $collection;
        $response = $this->postForm($base . '/uploads/context', [], $user);
        $token = json_decode((string) $response->getContent(), true)['context'];
        $first = tempnam(sys_get_temp_dir(), 'native-file-');
        $second = tempnam(sys_get_temp_dir(), 'native-image-');
        file_put_contents($first, 'First native file');
        file_put_contents($second, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aM1sAAAAASUVORK5CYII=', true));
        $form = ['name' => 'Unsaved file item', 'sort' => '100', 'detail_text' => 'Unsaved body', 'upload_context' => $token,
            'field_attachment' => ['', ''], 'file_search' => 'attachment', 'file_query' => ['attachment' => 'no matching name']];
        try {
            $response = $this->handleRequest($base . '/items', 'POST', $this->withCsrfToken($form), cookies: $this->actingAs($user), files: [
                'file_uploads' => ['attachment' => [
                    new UploadedFile($first, 'first.php', 'image/png', null, true),
                    new UploadedFile($second, 'second.php', 'text/plain', null, true),
                ]],
            ]);
        } finally {
            unlink($first);
            unlink($second);
        }
        self::assertSame(200, $response->getStatusCode());
        $html = (string) $response->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $chosen = [];
        foreach ($document->querySelectorAll('select[name="field_attachment[]"] option[selected]') as $option) {
            $chosen[] = $option->getAttribute('value');
        }
        self::assertCount(2, $chosen);
        self::assertNotContains('', $chosen);
        $this->files = [...$this->files, ...array_map(intval(...), $chosen)];
        self::assertNotNull($document->querySelector('[data-file-preview] img'));
        self::assertNotNull($document->querySelector('dialog[data-file-picker]'));
        self::assertStringContainsString('Unsaved file item', $html);
        self::assertStringContainsString('Unsaved body', $html);
        self::assertSame(0, Capsule::table('collection_items')->count());
        self::assertSame(0, Capsule::table('collection_file_value_links')->count());
        $chosen = array_reverse($chosen);
        self::assertSame(302, $this->postForm($base . '/items', ['name' => 'Saved', 'code' => 'saved', 'sort' => '100', 'upload_context' => $token, 'field_attachment' => $chosen], $user)->getStatusCode());
        self::assertSame($chosen, $this->container()->get(CollectionsApiInterface::class)->getItemByCode($collection, null, 'saved')->values['attachment']);
        self::assertSame(0, Capsule::table('collection_file_links')->count());
        self::assertSame(2, Capsule::table('collection_file_value_links')->count());
    }

    public function testNativeSaveUploadsAFileAndClearOnlyPreviewsUntilTheNextSave(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'native-single', 'name' => 'Single']);
        $this->container()->get(CollectionSchemaApiInterface::class)->saveField(null, new CollectionFieldFormDTO($collection, 'attachment', 'Attachment', FieldType::File, false, false, 100));
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $collection;
        $context = $this->postForm($base . '/uploads/context', [], $user);
        $token = json_decode((string) $context->getContent(), true)['context'];
        $path = tempnam(sys_get_temp_dir(), 'native-save-');
        file_put_contents($path, 'Native saved contents');
        try {
            $response = $this->handleRequest($base . '/items', 'POST', $this->withCsrfToken(['name' => 'Saved', 'code' => 'saved', 'sort' => '100', 'upload_context' => $token, 'field_attachment' => '']), cookies: $this->actingAs($user), files: [
                'file_uploads' => ['attachment' => [new UploadedFile($path, 'saved.txt', 'text/plain', null, true)]],
            ]);
        } finally {
            unlink($path);
        }
        self::assertSame(302, $response->getStatusCode());
        $api = $this->container()->get(CollectionsApiInterface::class);
        $item = $api->getItemByCode($collection, null, 'saved');
        $this->files[] = (int) $item->values['attachment'];
        $response = $this->postForm($base . '/items', ['id' => $item->id, 'name' => 'Unsaved rename', 'sort' => '100', 'field_attachment' => $item->values['attachment'], 'file_clear' => 'attachment:0'], $user);
        self::assertSame(200, $response->getStatusCode());
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        self::assertSame('', $document->querySelector('[data-file-select] option[selected]')->getAttribute('value'));
        self::assertSame('Saved', $api->getItem($item->id)->name);
        self::assertSame($item->values['attachment'], $api->getItem($item->id)->values['attachment']);
    }

    public function testPartialNativeUploadFailureKeepsSuccessfulSelectionsAndDoesNotSaveTheItem(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'partial-files', 'name' => 'Partial']);
        $this->container()->get(CollectionSchemaApiInterface::class)->saveField(null, new CollectionFieldFormDTO($collection, 'attachment', 'Attachments', FieldType::File, false, true, 100));
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $collection;
        $context = $this->postForm($base . '/uploads/context', [], $user);
        $token = json_decode((string) $context->getContent(), true)['context'];
        $first = tempnam(sys_get_temp_dir(), 'partial-good-');
        $second = tempnam(sys_get_temp_dir(), 'partial-bad-');
        file_put_contents($first, 'Valid file contents');
        file_put_contents($second, '<?php echo "unsupported";');
        try {
            $response = $this->handleRequest($base . '/items', 'POST', $this->withCsrfToken(['name' => 'Unsaved', 'sort' => '100', 'upload_context' => $token, 'field_attachment' => ['', '']]), cookies: $this->actingAs($user), files: [
                'file_uploads' => ['attachment' => [
                    new UploadedFile($first, 'good.txt', 'text/plain', null, true),
                    new UploadedFile($second, 'bad.txt', 'text/plain', null, true),
                ]],
            ]);
        } finally {
            unlink($first);
            unlink($second);
        }
        self::assertSame(200, $response->getStatusCode());
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        $chosen = [];
        foreach ($document->querySelectorAll('[data-file-select] option[selected]') as $option) {
            $chosen[] = $option->getAttribute('value');
        }
        self::assertCount(2, $chosen);
        self::assertNotSame('', $chosen[0]);
        self::assertSame('', $chosen[1]);
        $this->files[] = (int) $chosen[0];
        self::assertSame(0, Capsule::table('collection_items')->count());
        self::assertSame(1, Capsule::table('collection_file_links')->where('context_token', $token)->count());
    }

    public function testNativeFileCountAtTheServerBoundaryCannotPartiallySaveSelections(): void
    {
        $limit = $this->container()->get(UploadLimits::class)->maxFilesPerRequest();
        if ($limit < 1 || $limit > 1000) {
            self::markTestSkipped('This completeness scenario needs a finite PHP upload-count limit.');
        }
        $collection = Fixture::insert('collections', ['code' => 'batch-files', 'name' => 'Batch']);
        $this->container()->get(CollectionSchemaApiInterface::class)->saveField(null, new CollectionFieldFormDTO($collection, 'attachment', 'Attachments', FieldType::File, false, true, 100));
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $collection;
        $context = $this->postForm($base . '/uploads/context', [], $user);
        $token = json_decode((string) $context->getContent(), true)['context'];
        $path = tempnam(sys_get_temp_dir(), 'batch-file-');
        file_put_contents($path, 'Batch contents');
        try {
            $upload = new UploadedFile($path, 'batch.txt', 'text/plain', null, true);
            $response = $this->handleRequest($base . '/items', 'POST', $this->withCsrfToken(['name' => 'Batch', 'sort' => '100', 'upload_context' => $token, 'field_attachment' => array_fill(0, $limit, '')]), cookies: $this->actingAs($user), files: [
                'file_uploads' => ['attachment' => array_fill(0, $limit, $upload)],
            ]);
        } finally {
            unlink($path);
        }
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(0, Capsule::table('collection_items')->count());
        self::assertSame(0, Capsule::table('collection_file_links')->count());
    }

    private function storedFile(string $contents, string $name): int
    {
        $file = $this->container()->get(FileStore::class)->storeContents($contents, $name, 'file-widget-tests/' . bin2hex(random_bytes(8)));
        $this->files[] = $file->id;
        return $file->id;
    }
}
