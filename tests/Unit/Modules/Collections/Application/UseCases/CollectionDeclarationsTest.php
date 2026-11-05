<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\PdoConnection;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApi;
use Johncms\Modules\Collections\Application\DTO\CollectionDeclarationDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldDeclarationDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionLabelDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationRegistry;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationsSynchronizer;
use Johncms\Modules\Collections\Application\Services\CollectionOwnerUrls;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionFieldUseCase;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionFieldUseCase;
use Johncms\Modules\Collections\Application\Extension\CollectionOwnerUrlProviderInterface;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Updates\SystemOperationContext;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\Fixture;
use Tests\Support\MutableCollectionDeclarationProvider;
use Tests\Support\RunsMigrations;

final class CollectionDeclarationsTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private MutableCollectionDeclarationProvider $provider;
    private CollectionDeclarationsSynchronizer $sync;
    private SystemOperationContext $context;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('collections');
        CollectionItemWriterFactory::validator();
        $this->provider = new MutableCollectionDeclarationProvider([$this->declaration()]);
        $this->sync = new CollectionDeclarationsSynchronizer(new CollectionDeclarationRegistry([$this->provider]), new ContentCollectionRepository(), new ContentCollectionFieldRepository(), new CollectionTransaction());
        $this->context = new SystemOperationContext();
    }

    protected function tearDown(): void
    {
        TranslatorFunctions::register(new Translator());
        $this->shutdownDatabase();
    }

    private function declaration(array $fields = [], array $settings = ['has_sections' => true, 'per_page' => 20]): CollectionDeclarationDTO
    {
        return new CollectionDeclarationDTO('articles', 'articles', new CollectionLabelDTO('example', 'Articles'), 'collections.manage', $fields !== [] ? $fields : [$this->field()], $settings);
    }

    private function field(string $code = 'title', FieldType $type = FieldType::String_, bool $multiple = false, ?string $renamedFrom = null): CollectionFieldDeclarationDTO
    {
        return new CollectionFieldDeclarationDTO($code, new CollectionLabelDTO('example', 'Title'), $type, $multiple, role: 'meta_title', renamedFrom: $renamedFrom);
    }

    private function schema(): CollectionSchemaApi
    {
        $fields = new ContentCollectionFieldRepository();
        return new CollectionSchemaApi($fields,
            new SaveCollectionFieldUseCase($fields, new CollectionTransaction(), new ContentCollectionRepository(), \Tests\Support\CollectionItemWriterFactory::itemRepository(), context: $this->context),
            new DeleteCollectionFieldUseCase($fields, new CollectionTransaction(), new ContentCollectionRepository(), context: $this->context)
        );
    }

    private function collection(): int
    {
        return (new ContentCollectionRepository())->findByDeclaration('example/blog', 'articles')->id;
    }

    private function item(string $value = 'Original'): int
    {
        return CollectionItemWriterFactory::api()->addItem(new CollectionItemFormDTO($this->collection(), null, 'entry', 'Entry', true, null, null, 100, null, null, ['title' => $value]));
    }

    public function testCreatesOwnedSchemaAndRepeatedSynchronizationIsIdempotent(): void
    {
        $this->sync->synchronize();
        $this->sync->validate();
        $id = $this->collection();
        $field = $this->schema()->getFields($id)[0];
        self::assertSame('example/blog', $field->ownerModule);
        self::assertSame('meta_title', $field->role);
        self::assertSame('Title', $field->messageId);
        $this->sync->synchronize();
        self::assertSame($id, $this->collection());
        self::assertSame($field->id, $this->schema()->getFields($id)[0]->id);
        self::assertSame(1, Capsule::table('collections')->count());
        self::assertSame(1, Capsule::table('collection_fields')->count());
        self::assertSame($id, CollectionItemWriterFactory::api()->findDeclaredCollection('example/blog', 'articles')->id);
    }

    public function testAddsFieldsAndDefaultsWithoutReplacingUserChoicesOrValues(): void
    {
        $this->sync->synchronize();
        $id = $this->collection();
        $this->item();
        $custom = $this->schema()->saveField(null, new CollectionFieldFormDTO($id, 'custom', 'Custom', FieldType::String_, false, false, 100));
        Capsule::table('collections')->where('id', $id)->update(['settings' => json_encode(['per_page' => 50, 'custom' => 'kept'])]);
        $this->provider->declarations = [$this->declaration([$this->field(), new CollectionFieldDeclarationDTO('extra', new CollectionLabelDTO('example', 'Extra'), FieldType::Integer)], ['per_page' => 30, 'new_option' => true])];
        $this->sync->synchronize();
        $this->sync->validate();
        $settings = (new ContentCollectionRepository())->findById($id)->settings;
        self::assertSame(50, $settings['per_page']);
        self::assertSame('kept', $settings['custom']);
        self::assertTrue($settings['new_option']);
        self::assertSame(3, Capsule::table('collection_fields')->count());
        self::assertNull((new ContentCollectionFieldRepository())->findById($custom)->owner_module);
        self::assertSame('Original', CollectionItemWriterFactory::api()->getItemByCode($id, null, 'entry')->values['title']);
    }

    public function testExplicitRenameKeepsFieldIdAndEavValues(): void
    {
        $this->sync->synchronize();
        $field = $this->schema()->getFields($this->collection())[0];
        $item = $this->item();
        $this->provider->declarations = [$this->declaration([$this->field('headline', renamedFrom: 'title')])];
        $this->sync->synchronize();
        $this->sync->validate();
        self::assertSame($field->id, $this->schema()->getFields($this->collection())[0]->id);
        self::assertSame(['headline' => 'Original'], CollectionItemWriterFactory::api()->getItem($item)->values);
    }

    public function testDeclarationDoesNotRemoveAUsedOptionBeforeItsOwnerConvertsTheValues(): void
    {
        $label = new CollectionLabelDTO('example', 'Title');
        $old = [['code' => 'a', 'label' => 'A', 'active' => true]];
        $new = [['code' => 'b', 'label' => 'B', 'active' => true]];
        $this->provider->declarations = [$this->declaration([new CollectionFieldDeclarationDTO('title', $label, FieldType::Select, settings: ['options' => $old])])];
        $this->sync->synchronize();
        $item = $this->item('a');
        $id = $this->collection();
        $field = $this->schema()->getFields($id)[0];
        $this->provider->declarations = [$this->declaration([new CollectionFieldDeclarationDTO('title', $label, FieldType::Select, settings: ['options' => $new])])];
        $this->sync->synchronize();
        self::assertNotEmpty($this->sync->problems());
        self::assertSame($old, $this->schema()->getFields($id)[0]->settings['options']);
        self::assertSame('a', CollectionItemWriterFactory::api()->getItem($item)->values['title']);
        $this->context->runTrusted(function () use ($id, $item, $field, $new): void {
            (new PdoConnection(Capsule::connection()->getPdo()))->transaction(function () use ($id, $item, $field, $new): void {
                $this->schema()->saveField($field->id, new CollectionFieldFormDTO($id, 'title', 'Title', FieldType::Select, false, false, 100, settings: ['options' => $new]));
                CollectionItemWriterFactory::api()->patchItem($item, new CollectionItemPatchDTO(values: ['title' => 'b']));
            });
        });
        $this->sync->validate();
        self::assertSame('b', CollectionItemWriterFactory::api()->getItem($item)->values['title']);
    }

    public function testIncompatibleTypeWaitsForTrustedHookAndPreservesData(): void
    {
        $this->sync->synchronize();
        $id = $this->collection();
        $item = $this->item('12');
        $this->provider->declarations = [$this->declaration([$this->field(type: FieldType::Integer, multiple: true)])];
        $this->sync->synchronize();
        self::assertNotEmpty($this->sync->problems());
        $field = $this->schema()->getFields($id)[0];
        self::assertSame(FieldType::String_, $field->type);
        self::assertSame('12', CollectionItemWriterFactory::api()->getItem($item)->values['title']);
        $this->context->runTrusted(function () use ($id, $item, $field): void {
            (new PdoConnection(Capsule::connection()->getPdo()))->transaction(function () use ($id, $item, $field): void {
                $this->schema()->saveField($field->id, new CollectionFieldFormDTO($id, 'title', $field->messageId, FieldType::Integer, false, true, 100, role: 'meta_title'));
                CollectionItemWriterFactory::api()->patchItem($item, new CollectionItemPatchDTO(values: ['title' => ['12']]));
            });
        });
        $this->sync->validate();
        self::assertSame([12], CollectionItemWriterFactory::api()->getItem($item)->values['title']);
    }

    public function testHookCanReplaceAnOwnedFieldWithoutLosingValues(): void
    {
        $this->sync->synchronize();
        $id = $this->collection();
        $item = $this->item('12');
        $old = $this->schema()->getFields($id)[0];
        $this->provider->declarations = [$this->declaration([$this->field(type: FieldType::Integer)])];
        $this->sync->synchronize();
        $this->context->runTrusted(function () use ($id, $item, $old): void {
            (new PdoConnection(Capsule::connection()->getPdo()))->transaction(function () use ($id, $item, $old): void {
                $temporary = $this->schema()->saveOwnedField($id, null, new CollectionFieldDeclarationDTO('converted', new CollectionLabelDTO('example', 'Title'), FieldType::Integer));
                CollectionItemWriterFactory::api()->patchItem($item, new CollectionItemPatchDTO(values: ['converted' => '12']));
                $this->schema()->deleteField($old->id);
                $this->schema()->saveOwnedField($id, $temporary, $this->field(type: FieldType::Integer));
            });
        });
        $this->sync->validate();
        self::assertSame(12, CollectionItemWriterFactory::api()->getItem($item)->values['title']);
        self::assertNotSame($old->id, $this->schema()->getFields($id)[0]->id);
    }

    public function testCustomCodeCollisionIsNotAdoptedAndCanBeResolvedByRenaming(): void
    {
        $this->sync->synchronize();
        $id = $this->collection();
        $owned = $this->schema()->getFields($id)[0];
        $this->context->runTrusted(fn () => $this->schema()->deleteField($owned->id));
        $custom = $this->schema()->saveField(null, new CollectionFieldFormDTO($id, 'title', 'Custom title', FieldType::String_, false, false, 100));
        $item = $this->item();
        $this->sync->synchronize();
        self::assertStringContainsString('administrator', implode(' ', $this->sync->problems()));
        self::assertNull((new ContentCollectionFieldRepository())->findById($custom)->owner_module);
        $this->schema()->saveField($custom, new CollectionFieldFormDTO($id, 'custom_title', 'Custom title', FieldType::String_, false, false, 100));
        $this->sync->synchronize();
        $this->sync->validate();
        self::assertSame('Original', CollectionItemWriterFactory::api()->getItem($item)->values['custom_title']);
        self::assertCount(2, $this->schema()->getFields($id));
    }

    public function testCustomCollectionWithTheDefaultCodeIsNotAdopted(): void
    {
        $id = Fixture::insert('collections', ['code' => 'articles', 'name' => 'Custom']);
        $this->sync->synchronize();
        self::assertNull((new ContentCollectionRepository())->findById($id)->owner_module);
        self::assertNotEmpty($this->sync->problems());
        self::assertSame(0, Capsule::table('collection_fields')->count());
    }

    public function testOwnedFieldsCannotBeDeletedOrChangedOutsideTheHook(): void
    {
        $this->sync->synchronize();
        $field = $this->schema()->getFields($this->collection())[0];
        foreach ([fn () => $this->schema()->deleteField($field->id), fn () => $this->schema()->saveField($field->id, new CollectionFieldFormDTO($this->collection(), 'forged', 'Forged', FieldType::Integer, false, false, 100))] as $action) {
            try {
                $action();
                self::fail('An ordinary operation changed owned schema.');
            } catch (CollectionSchemaException) {
                self::assertSame('title', $this->schema()->getFields($this->collection())[0]->code);
            }
        }
    }

    public function testLabelsRemainSourceMessagesAndFollowTheCurrentLocale(): void
    {
        $this->sync->synchronize();
        $collection = (new ContentCollectionRepository())->findById($this->collection());
        $field = (new ContentCollectionFieldRepository())->getByCollection($collection->id)->first();
        $translator = new Translator();
        $translator->addTranslations(['domain' => 'example', 'messages' => ['' => ['Articles' => 'Статьи', 'Title' => 'Заголовок']]]);
        TranslatorFunctions::register($translator);
        self::assertSame('Статьи', $collection->name);
        self::assertSame('Заголовок', $field->name);
        TranslatorFunctions::register(new Translator());
        self::assertSame('Articles', $collection->name);
        self::assertSame('Title', $field->name);
        self::assertSame('Articles', $collection->getRawOriginal('name'));
        self::assertSame('Title', $field->getRawOriginal('name'));
    }

    public function testOwnerUrlsAreProvidedWithoutCreatingCollectionUrls(): void
    {
        $this->sync->synchronize();
        $item = CollectionItemWriterFactory::api()->getItem($this->item());
        $collection = CollectionItemWriterFactory::api()->findDeclaredCollection('example/blog', 'articles');
        $provider = $this->createMock(CollectionOwnerUrlProviderInterface::class);
        $provider->method('owner')->willReturn('example/blog');
        $provider->method('itemUrl')->willReturn('/owner/entry.html');
        $provider->method('previewUrl')->willReturn('/admin/owner/preview');
        $urls = new CollectionOwnerUrls([$provider]);
        self::assertSame('/owner/entry.html', $urls->item($collection, $item));
        self::assertSame('/admin/owner/preview', $urls->preview($collection, $item));
        self::assertNull((new CollectionOwnerUrls())->item($collection, $item));
        Capsule::table('collections')->where('id', $collection->id)->update(['public' => true]);
        self::assertSame('', (new ContentCollectionRepository())->findById($collection->id)->publicUrl());
        self::assertSame([], (new ContentCollectionRepository())->getPublicCodeMap());
    }

    public function testUnsafeOwnerUrlIsRejected(): void
    {
        $this->sync->synchronize();
        $api = CollectionItemWriterFactory::api();
        $provider = $this->createMock(CollectionOwnerUrlProviderInterface::class);
        $provider->method('owner')->willReturn('example/blog');
        $provider->method('itemUrl')->willReturn('javascript:alert(1)');
        $this->expectException(\InvalidArgumentException::class);
        (new CollectionOwnerUrls([$provider]))->item($api->findDeclaredCollection('example/blog', 'articles'), $api->getItem($this->item()));
    }
}
