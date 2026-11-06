<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Files\FileStore;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class CollectionUploadsTest extends FunctionalTestCase
{
    private int $collection;
    /** @var list<int> */
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = Fixture::insert('collections', ['code' => 'uploads', 'name' => 'Uploads', 'settings' => json_encode(['_content_permission' => 'news.manage'])]);
    }

    protected function tearDown(): void
    {
        $this->container()->get(FileStore::class)->deleteMany($this->files);
        parent::tearDown();
    }

    public function testFieldUploadUsesItsContentPolicyAndRetainsOnlyTheSelectedFieldReference(): void
    {
        $schema = $this->container()->get(\Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface::class);
        $field = $schema->saveField(null, new \Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO(
            collectionId: $this->collection, code: 'attachment', name: 'Attachment', type: \Johncms\Modules\Collections\Domain\Enums\FieldType::File, required: false, multiple: false, sort: 100,
            settings: ['allowed_mime_types' => ['text/plain'], 'max_file_bytes' => 32]
        ));
        $user = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $base = '/admin/collections/' . $this->collection;
        $token = $this->context($base, $user);
        $path = tempnam(sys_get_temp_dir(), 'field-upload-');
        file_put_contents($path, 'Verified contents');
        try {
            $response = $this->handleRequest($base . '/uploads?field=attachment&context=' . $token, 'POST', $this->withCsrfToken(), cookies: $this->actingAs($user), files: [
                'upload' => new UploadedFile($path, 'spoof.php', 'image/png', null, true),
            ]);
        } finally {
            unlink($path);
        }
        self::assertSame(200, $response->getStatusCode());
        $file = json_decode((string) $response->getContent(), true);
        $this->files[] = $file['id'];
        self::assertSame('spoof.txt', $file['name']);
        self::assertStringEndsWith('.txt', $file['url']);
        self::assertSame($field, Capsule::table('collection_file_links')->where('file_id', $file['id'])->value('field_id'));
        self::assertSame(302, $this->postForm($base . '/items', ['name' => 'Field item', 'sort' => '100', 'upload_context' => $token, 'field_attachment' => (string) $file['id']], $user)->getStatusCode());
        self::assertSame(0, Capsule::table('collection_file_links')->where('file_id', $file['id'])->count());
        self::assertSame(1, Capsule::table('collection_file_value_links')->where('file_id', $file['id'])->count());
        $path = tempnam(sys_get_temp_dir(), 'field-upload-');
        file_put_contents($path, str_repeat('x', 33));
        try {
            $token = $this->context($base, $user);
            $response = $this->handleRequest($base . '/uploads?field=attachment&context=' . $token, 'POST', $this->withCsrfToken(), cookies: $this->actingAs($user), files: [
                'upload' => new UploadedFile($path, 'too-large.txt', 'text/plain', null, true),
            ]);
            self::assertSame(422, $response->getStatusCode());
        } finally {
            unlink($path);
        }
    }

    public function testFieldImageKeepsItsAllowedFormatWhenTheEditorConvertsToWebp(): void
    {
        $this->container()->get(\Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface::class)->saveField(null, new \Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO(
            collectionId: $this->collection, code: 'cover', name: 'Cover', type: \Johncms\Modules\Collections\Domain\Enums\FieldType::File, required: false, multiple: false, sort: 100,
            settings: ['allowed_mime_types' => ['image/png']]
        ));
        $user = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $base = '/admin/collections/' . $this->collection;
        $token = $this->context($base, $user);
        $path = $this->picture();
        try {
            $response = $this->withConfig(['johncms' => ['editor_images' => ['convert' => 'webp']]], fn () => $this->handleRequest(
                $base . '/uploads?field=cover&context=' . $token, 'POST', $this->withCsrfToken(), cookies: $this->actingAs($user),
                files: ['upload' => new UploadedFile($path, 'cover.php', 'text/plain', null, true)]
            ));
        } finally {
            unlink($path);
        }
        self::assertSame(200, $response->getStatusCode());
        $file = json_decode((string) $response->getContent(), true);
        $this->files[] = $file['id'];
        self::assertSame('cover.png', $file['name']);
        self::assertStringEndsWith('.png', $file['url']);
    }

    public function testNewItemClaimsUploadsAndValidationFailureKeepsTheContext(): void
    {
        $user = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $base = '/admin/collections/' . $this->collection;
        $response = $this->handleRequest($base . '/items/new', cookies: $this->actingAs($user));
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(1, preg_match('/name="upload_context" value="([a-f0-9]{64})"/', (string) $response->getContent(), $match));
        $token = $match[1];
        $file = $this->upload($base, $token, $user);
        $form = ['upload_context' => $token, 'name' => '', 'code' => 'new-entry', 'sort' => '100', 'detail_text' => '<img src="' . $file['url'] . '">'];
        self::assertSame(200, $this->postForm($base . '/items', $form, $user)->getStatusCode());
        self::assertTrue(Capsule::table('collection_upload_contexts')->where('token', $token)->exists());
        $form['name'] = 'New entry';
        self::assertSame(302, $this->postForm($base . '/items', $form, $user)->getStatusCode());
        $item = Capsule::table('collection_items')->where('code', 'new-entry')->first();
        self::assertNotNull($item);
        self::assertSame($item->id, Capsule::table('collection_file_links')->where('file_id', $file['id'])->value('item_id'));
        self::assertFalse(Capsule::table('collection_upload_contexts')->where('token', $token)->exists());
        self::assertNotNull($this->container()->get(FileStore::class)->find($file['id']));
    }

    public function testOtherEditorAndCollectionCannotUseTheContextOrSpoofAttachedFileIds(): void
    {
        $owner = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $other = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $base = '/admin/collections/' . $this->collection;
        $token = $this->context($base, $owner);
        $file = $this->upload($base, $token, $owner);
        $path = $this->picture();
        try {
            $response = $this->handleRequest($base . '/uploads?context=' . $token, 'POST', $this->withCsrfToken(), cookies: $this->actingAs($other), files: ['upload' => new UploadedFile($path, 'test.png', 'image/png', null, true)]);
            self::assertSame(422, $response->getStatusCode());
        } finally {
            unlink($path);
        }
        $form = ['upload_context' => $token, 'name' => 'Forged', 'code' => 'forged', 'sort' => '100'];
        self::assertSame(200, $this->postForm($base . '/items', $form, $other)->getStatusCode());
        self::assertFalse(Capsule::table('collection_items')->where('code', 'forged')->exists());
        unset($form['upload_context']);
        $form['attached_files'] = [$file['id']];
        self::assertSame(302, $this->postForm($base . '/items', $form, $other)->getStatusCode());
        self::assertNull(Capsule::table('collection_file_links')->where('file_id', $file['id'])->value('item_id'));
        $foreign = Fixture::insert('collections', ['code' => 'foreign-upload', 'name' => 'Foreign']);
        self::assertSame(403, $this->postForm('/admin/collections/' . $foreign . '/uploads/context', [], $other)->getStatusCode());
    }

    public function testAnExistingItemContextCannotBeClaimedByAnotherItem(): void
    {
        $user = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $first = Fixture::insert('collection_items', ['collection_id' => $this->collection, 'code' => 'first', 'name' => 'First']);
        $second = Fixture::insert('collection_items', ['collection_id' => $this->collection, 'code' => 'second', 'name' => 'Second']);
        $base = '/admin/collections/' . $this->collection;
        $token = $this->context($base, $user, $first);
        $this->upload($base, $token, $user);
        self::assertSame(200, $this->postForm($base . '/items', ['id' => $second, 'upload_context' => $token, 'name' => 'Forged', 'code' => 'second', 'sort' => '100'], $user)->getStatusCode());
        self::assertSame('Second', Capsule::table('collection_items')->where('id', $second)->value('name'));
        self::assertSame(0, Capsule::table('collection_file_links')->where('item_id', $second)->count());
    }

    public function testAFormSavesAfterItsContextLapsedOrWasRemoved(): void
    {
        $user = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $item = Fixture::insert('collection_items', ['collection_id' => $this->collection, 'code' => 'first', 'name' => 'First']);
        $base = '/admin/collections/' . $this->collection;
        $lapsed = $this->context($base, $user, $item);
        Capsule::table('collection_upload_contexts')->where('token', $lapsed)->update(['created_at' => '2000-01-01 00:00:00']);
        $form = ['id' => $item, 'code' => 'first', 'sort' => '100'];
        self::assertSame(302, $this->postForm($base . '/items', $form + ['upload_context' => $lapsed, 'name' => 'After a day'], $user)->getStatusCode());
        self::assertSame('After a day', Capsule::table('collection_items')->where('id', $item)->value('name'));
        $removed = $this->context($base, $user, $item);
        Capsule::table('collection_upload_contexts')->where('token', $removed)->delete();
        self::assertSame(302, $this->postForm($base . '/items', $form + ['upload_context' => $removed, 'name' => 'After cleanup'], $user)->getStatusCode());
        self::assertSame('After cleanup', Capsule::table('collection_items')->where('id', $item)->value('name'));
    }

    public function testALapsedContextKeepsItsFilesAndTheFormRenewsIt(): void
    {
        $user = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $base = '/admin/collections/' . $this->collection;
        $token = $this->context($base, $user);
        $file = $this->upload($base, $token, $user);
        Capsule::table('collection_upload_contexts')->where('token', $token)->update(['created_at' => '2000-01-01 00:00:00']);
        $form = ['upload_context' => $token, 'name' => '', 'code' => 'late', 'sort' => '100'];
        self::assertSame(200, $this->postForm($base . '/items', $form, $user)->getStatusCode());
        self::assertSame(gmdate('Y-m-d'), substr((string) Capsule::table('collection_upload_contexts')->where('token', $token)->value('created_at'), 0, 10));
        self::assertGreaterThan($file['id'], $this->upload($base, $token, $user)['id']);
        Capsule::table('collection_upload_contexts')->where('token', $token)->update(['created_at' => '2000-01-01 00:00:00']);
        $form['name'] = 'Late';
        self::assertSame(302, $this->postForm($base . '/items', $form, $user)->getStatusCode());
        $item = Capsule::table('collection_items')->where('code', 'late')->value('id');
        self::assertSame($item, Capsule::table('collection_file_links')->where('file_id', $file['id'])->value('item_id'));
    }

    public function testExpiredContextsAndNonImagesAreRejectedWithoutCreatingFiles(): void
    {
        $user = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $base = '/admin/collections/' . $this->collection;
        $token = $this->context($base, $user);
        Capsule::table('collection_upload_contexts')->where('token', $token)->update(['created_at' => '2000-01-01 00:00:00']);
        $path = $this->picture();
        try {
            $response = $this->handleRequest($base . '/uploads?context=' . $token, 'POST', $this->withCsrfToken(), cookies: $this->actingAs($user), files: ['upload' => new UploadedFile($path, 'test.png', 'image/png', null, true)]);
            self::assertSame(422, $response->getStatusCode());
        } finally {
            unlink($path);
        }
        $token = $this->context($base, $user);
        $path = tempnam(sys_get_temp_dir(), 'collection-upload-test-');
        file_put_contents($path, '<?php echo "not an image";');
        try {
            $response = $this->handleRequest($base . '/uploads?context=' . $token, 'POST', $this->withCsrfToken(), cookies: $this->actingAs($user), files: ['upload' => new UploadedFile($path, 'test.png', 'image/png', null, true)]);
            self::assertArrayHasKey('error', json_decode((string) $response->getContent(), true));
            self::assertSame(0, Capsule::table('collection_file_links')->count());
        } finally {
            unlink($path);
        }
    }

    private function context(string $base, \Johncms\Users\User $user, ?int $itemId = null): string
    {
        $response = $this->postForm($base . '/uploads/context', ['item_id' => $itemId], $user);
        self::assertSame(200, $response->getStatusCode());
        return json_decode((string) $response->getContent(), true)['context'];
    }

    /** @return array{id: int, url: string} */
    private function upload(string $base, string $token, \Johncms\Users\User $user): array
    {
        $path = $this->picture();
        try {
            $response = $this->handleRequest($base . '/uploads?context=' . $token, 'POST', $this->withCsrfToken(), cookies: $this->actingAs($user), files: ['upload' => new UploadedFile($path, 'test.png', 'image/png', null, true)]);
            self::assertSame(200, $response->getStatusCode());
            $data = json_decode((string) $response->getContent(), true);
            self::assertSame(1, $data['uploaded'] ?? null, (string) $response->getContent());
            $this->files[] = $data['id'];
            return $data;
        } finally {
            unlink($path);
        }
    }

    private function picture(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'collection-upload-test-');
        $image = imagecreatetruecolor(2, 2);
        imagefill($image, 0, 0, imagecolorallocate($image, 20, 40, 60));
        imagepng($image, $path);
        imagedestroy($image);
        return $path;
    }
}
