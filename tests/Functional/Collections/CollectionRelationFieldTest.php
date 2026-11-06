<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Dom\HTMLDocument;
use Illuminate\Database\Capsule\Manager;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsUserApi;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionAccessDeniedException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\Services\CollectionTagSuggestions;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionItemWriteAccessUseCase;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionRelationAccessUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Enums\SectionDeletionMode;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use RuntimeException;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;
use Tests\Support\RecordingCollectionChangeHandler;

final class CollectionRelationFieldTest extends FunctionalTestCase
{
    private int $source;
    private int $target;
    private CollectionsApiInterface $api;
    private CollectionSchemaApiInterface $schema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->source = Fixture::insert('collections', ['code' => 'relations', 'name' => 'Relations', 'active' => true]);
        $this->target = Fixture::insert('collections', ['code' => 'targets', 'name' => 'Targets', 'active' => true]);
        $this->api = $this->container()->get(CollectionsApiInterface::class);
        $this->schema = $this->container()->get(CollectionSchemaApiInterface::class);
    }

    public function testRelationWritesCheckMembershipForSingleMultipleAndPatch(): void
    {
        $this->field($this->target);
        $target = $this->api->addItem($this->form($this->target, 'target'));
        $foreign = $this->api->addItem($this->form($this->source, 'foreign'));
        $item = $this->api->addItem($this->form($this->source, 'source', (string) $target));
        self::assertSame($target, $this->api->getItem($item)->values['related']);
        foreach ([$foreign, PHP_INT_MAX] as $invalid) {
            try {
                $this->api->patchItem($item, new CollectionItemPatchDTO(values: ['related' => (string) $invalid]));
                self::fail('Invalid relation must roll back.');
            } catch (CollectionItemFormException $exception) {
                self::assertArrayHasKey('values.related', $exception->errors);
                self::assertSame($target, $this->api->getItem($item)->values['related']);
            }
        }
        $this->schema->saveField(null, new CollectionFieldFormDTO(collectionId: $this->source, code: 'many', name: 'Many', type: FieldType::Relation, required: false, multiple: true, sort: 100, settings: ['target_collection_id' => $this->target]));
        $this->api->patchItem($item, new CollectionItemPatchDTO(values: ['many' => [(string) $target]]));
        self::assertSame([$target], $this->api->getItem($item)->values['many']);
        $this->expectException(CollectionItemFormException::class);
        $this->api->patchItem($item, new CollectionItemPatchDTO(values: ['many' => [(string) $target, (string) $foreign]]));
    }

    public function testReferencedTargetsCannotBeDeletedThroughApiOrAdmin(): void
    {
        $this->field($this->target);
        $target = $this->api->addItem($this->form($this->target, 'target'));
        $source = $this->api->addItem($this->form($this->source, 'source', (string) $target));
        try {
            $this->api->deleteItem($target);
            self::fail('Incoming relation must prevent deletion.');
        } catch (CollectionDeletionException $exception) {
            self::assertArrayHasKey('relations', $exception->errors);
            self::assertNotNull($this->api->getItem($target));
        }
        $response = $this->postForm('/admin/collections/' . $this->target . '/items/' . $target . '/delete', [], FunctionalUserFactory::createSupervisor());
        self::assertSame(200, $response->getStatusCode());
        self::assertNotNull($this->api->getItem($target));
        $this->api->patchItem($source, new CollectionItemPatchDTO(values: ['related' => null]));
        $this->api->deleteItem($target);
        self::assertNull($this->api->getItem($target));
    }

    public function testTargetSettingsDefaultsAndRetargetingAreValidated(): void
    {
        foreach ([[], ['target_collection_id' => '1'], ['target_collection_id' => PHP_INT_MAX]] as $settings) {
            try {
                $this->schema->saveField(null, new CollectionFieldFormDTO(collectionId: $this->source, code: 'related', name: 'Related', type: FieldType::Relation, required: false, multiple: false, sort: 100, settings: $settings));
                self::fail('A relation requires an existing target collection.');
            } catch (CollectionSchemaException) {
                self::assertSame([], $this->schema->getFields($this->source));
            }
        }
        $target = $this->api->addItem($this->form($this->target, 'target'));
        $field = $this->field(target: $this->target, settings: ['default' => (string) $target]);
        $source = $this->api->addItem(new CollectionItemFormDTO($this->source, null, 'default', 'Default', true, null, null, 100, null, null, []));
        self::assertSame($target, $this->api->getItem($source)->values['related']);
        try {
            $this->field($this->source, $field, ['default' => null]);
            self::fail('Filled relations require explicit conversion.');
        } catch (CollectionSchemaException) {
            self::assertSame($this->target, $this->schema->getFields($this->source)[0]->settings['target_collection_id']);
        }
        $this->api->deleteItem($source);
        $this->field($this->target, $field, ['default' => null]);
        $this->api->deleteItem($target);
        $this->expectException(CollectionSchemaException::class);
        $this->field($this->target, $field, ['default' => (string) $target]);
    }

    public function testWholeCollectionDeletionRequiresRemovingExternalRelationFields(): void
    {
        $field = $this->field($this->target);
        try {
            $this->api->deleteCollection($this->target);
            self::fail('Deleting the target must not leave an invalid relation schema.');
        } catch (CollectionDeletionException $exception) {
            self::assertArrayHasKey('relations', $exception->errors);
        }
        $this->schema->deleteField($field);
        $this->api->deleteCollection($this->target);
        self::assertNull($this->api->findCollection('targets'));
    }

    public function testAtomicSubtreeDeletionIgnoresItsOwnRelations(): void
    {
        $sections = $this->container()->get(CollectionSectionsApiInterface::class);
        $section = $sections->addSection(new CollectionSectionFormDTO($this->source, null, 'branch', 'Branch', null, true, 100));
        $this->field($this->source);
        $first = $this->api->addItem($this->form(collection: $this->source, code: 'first', section: $section));
        $second = $this->api->addItem($this->form($this->source, 'second', (string) $first, $section));
        $this->api->patchItem($first, new CollectionItemPatchDTO(values: ['related' => (string) $second]));
        $sections->deleteSection($this->source, $section, SectionDeletionMode::DeleteItems);
        self::assertNull($this->api->getItem($first));
        self::assertNull($this->api->getItem($second));
    }

    public function testPickerSearchPreservesNativeFormAndIncludesSelectedItemsOutsideTheResultPage(): void
    {
        $this->field($this->target);
        $this->schema->saveField(null, new CollectionFieldFormDTO(collectionId: $this->source, code: 'many', name: 'Many related items', type: FieldType::Relation, required: false, multiple: true, sort: 100, settings: ['target_collection_id' => $this->target]));
        for ($index = 0; $index < 31; ++$index) {
            $this->api->addItem($this->form($this->target, 'ordinary-' . $index));
        }
        $target = $this->api->addItem(new CollectionItemFormDTO($this->target, null, 'needle', 'Needle <b>target</b>', true, null, null, 100, null, null, []));
        $user = FunctionalUserFactory::createSupervisor();
        $base = '/admin/collections/' . $this->source;
        $response = $this->handleRequest($base . '/relations/related?q=Needle', cookies: $this->actingAs($user));
        self::assertSame(200, $response->getStatusCode());
        $options = json_decode((string) $response->getContent(), true)['options'];
        self::assertSame([['code' => (string) $target, 'label' => 'Needle <b>target</b>']], $options);
        $literal = $this->handleRequest($base . '/relations/related?q=%25', cookies: $this->actingAs($user));
        self::assertSame([], json_decode((string) $literal->getContent(), true)['options']);
        self::assertSame(400, $this->handleRequest($base . '/relations/related?q=' . str_repeat('a', 256), cookies: $this->actingAs($user))->getStatusCode());
        $response = $this->postForm($base . '/items', [
            'name' => 'Unsaved source', 'sort' => '100', 'detail_text' => 'Unsaved body', 'field_related' => (string) $target,
            'field_many' => [(string) $target], 'relation_search' => 'related', 'relation_query' => ['related' => 'ordinary-0'],
        ], $user);
        self::assertSame(200, $response->getStatusCode());
        $html = (string) $response->getContent();
        self::assertStringContainsString('Unsaved source', $html);
        self::assertStringContainsString('Unsaved body', $html);
        self::assertStringContainsString('Needle &lt;b&gt;target&lt;/b&gt;', $html);
        self::assertSame((string) $target, HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelector('select[name="field_related"] option[selected]')->getAttribute('value'));
        self::assertSame(32, Manager::table('collection_items')->count());
    }

    public function testTargetPermissionsProtectSearchFormsAndForgedWrites(): void
    {
        Manager::table('collections')->where('id', $this->source)->update(['settings' => json_encode(['_content_permission' => 'news.manage'])]);
        Manager::table('collections')->where('id', $this->target)->update(['settings' => json_encode(['_content_permission' => 'forum.manage'])]);
        $this->field($this->target);
        $target = $this->api->addItem(new CollectionItemFormDTO($this->target, null, 'secret', 'Secret target title', true, null, null, 100, null, null, []));
        $editor = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $base = '/admin/collections/' . $this->source;
        $response = $this->handleRequest($base . '/items/new', cookies: $this->actingAs($editor));
        self::assertSame(200, $response->getStatusCode());
        self::assertStringNotContainsString('Secret target title', (string) $response->getContent());
        self::assertSame(403, $this->handleRequest($base . '/relations/related', cookies: $this->actingAs($editor))->getStatusCode());
        self::assertSame(403, $this->postForm($base . '/items', ['name' => 'Forged', 'sort' => '100', 'field_related' => (string) $target], $editor)->getStatusCode());
        self::assertSame(1, Manager::table('collection_items')->count());
        $permitted = FunctionalUserFactory::createWithPermissions(['news.manage', 'forum.manage']);
        self::assertSame(200, $this->handleRequest($base . '/relations/related', cookies: $this->actingAs($permitted))->getStatusCode());
        self::assertSame(302, $this->postForm($base . '/items', ['name' => 'Authorized', 'sort' => '100', 'field_related' => (string) $target], $permitted)->getStatusCode());
    }

    public function testEditorSavesARecordWhoseRelationTheyCannotOpenButCannotPointItElsewhere(): void
    {
        Manager::table('collections')->where('id', $this->source)->update(['settings' => json_encode(['_content_permission' => 'news.manage'])]);
        Manager::table('collections')->where('id', $this->target)->update(['settings' => json_encode(['_content_permission' => 'forum.manage'])]);
        $this->field($this->target);
        $linked = $this->api->addItem($this->form($this->target, 'linked'));
        $other = $this->api->addItem($this->form($this->target, 'other'));
        $source = $this->api->addItem($this->form($this->source, 'source', (string) $linked));
        $editor = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $form = ['id' => (string) $source, 'name' => 'Renamed', 'code' => 'source', 'sort' => '100', 'active' => '1'];
        self::assertSame(302, $this->postForm('/admin/collections/' . $this->source . '/items', $form + ['field_related' => (string) $linked], $editor)->getStatusCode());
        self::assertSame('Renamed', $this->api->getItem($source)->name);
        self::assertSame($linked, $this->api->getItem($source)->values['related']);
        self::assertSame(403, $this->postForm('/admin/collections/' . $this->source . '/items', $form + ['field_related' => (string) $other], $editor)->getStatusCode());
        self::assertSame($linked, $this->api->getItem($source)->values['related']);
    }

    public function testUserApiChecksRelationDefaultsAndPartialWritesWithoutBlockingExplicitClears(): void
    {
        $target = $this->api->addItem($this->form($this->target, 'target'));
        $this->field(target: $this->target, settings: ['default' => (string) $target]);
        $source = $this->api->addItem($this->form($this->source, 'source', (string) $target));
        $access = $this->createStub(AccessCheckerInterface::class);
        // A source-specific permission is enough for editing the source, but not its target.
        Manager::table('collections')->where('id', $this->source)->update(['settings' => json_encode(['_content_permission' => 'news.manage'])]);
        $access->method('allows')->willReturnCallback(static fn (string $permission): bool => $permission === 'news.manage');
        $this->handleRequest('/admin/collections/' . $this->source . '/items', cookies: $this->actingAs(FunctionalUserFactory::createWithPermissions(['news.manage'])));
        $guard = new EnsureCollectionContentAccessUseCase($access, $this->container()->get(ContentCollectionRepositoryInterface::class));
        $api = new CollectionsUserApi(
            $guard, $this->api, $this->container()->get(CollectionSectionsApiInterface::class), $this->container()->get(CurrentUser::class),
            $this->container()->get(CollectionTagSuggestions::class),
            new EnsureCollectionRelationAccessUseCase($this->container()->get(ContentCollectionFieldRepositoryInterface::class), $guard, $this->container()->get(ContentCollectionItemRepositoryInterface::class)),
            $this->container()->get(ContentCollectionRepositoryInterface::class),
            new CollectionTransaction(),
            new EnsureCollectionItemWriteAccessUseCase($guard, $this->container()->get(ContentCollectionItemRepositoryInterface::class)),
            $this->container()->get(ContentCollectionSectionRepositoryInterface::class)
        );
        $other = $this->api->addItem($this->form($this->target, 'other'));
        foreach ([
            fn () => $api->addItem(new CollectionItemFormDTO($this->source, null, 'default', 'Default', true, null, null, 100, null, null, [])),
            fn () => $api->updateItem($source, $this->form($this->source, 'source', (string) $other)),
            fn () => $api->patchItem($this->source, $source, new CollectionItemPatchDTO(values: ['related' => (string) $other])),
        ] as $write) {
            try {
                $write();
                self::fail('The user facade must check the target of every new reference, defaults included.');
            } catch (CollectionAccessDeniedException) {
                self::assertSame($target, $this->api->getItem($source)->values['related']);
            }
        }
        $api->updateItem($source, $this->form($this->source, 'source', (string) $target));
        $api->patchItem($this->source, $source, new CollectionItemPatchDTO(values: ['related' => (string) $target]));
        self::assertSame($target, $this->api->getItem($source)->values['related']);
        $api->patchItem($this->source, $source, new CollectionItemPatchDTO(values: ['related' => null]));
        self::assertArrayNotHasKey('related', $this->api->getItem($source)->values);
    }

    public function testClearRemovesOnlyDeletedTargetsAndDispatchesSourceChangesAtomically(): void
    {
        $this->schema->saveField(null, new CollectionFieldFormDTO(collectionId: $this->source, code: 'many', name: 'Many', type: FieldType::Relation, required: false, multiple: true, sort: 100, settings: ['target_collection_id' => $this->target, 'on_delete' => 'clear']));
        $first = $this->api->addItem($this->form($this->target, 'first'));
        $second = $this->api->addItem($this->form($this->target, 'second'));
        $source = $this->api->addItem(new CollectionItemFormDTO($this->source, null, 'source', 'Source', true, null, null, 100, null, null, []));
        $this->api->patchItem($source, new CollectionItemPatchDTO(values: ['many' => [(string) $second, (string) $first, (string) $second]]));
        Manager::table('collection_items')->where('id', $source)->update(['updated_at' => '2000-01-01 00:00:00']);
        $handler = new RecordingCollectionChangeHandler();
        $changes = new CollectionChangeDispatcher(handlers: [$handler], transaction: new CollectionTransaction());
        $deleter = CollectionItemWriterFactory::itemDeleter($changes);
        $handler->failWithin = true;
        try {
            $deleter->execute($first);
            self::fail('A source handler failure must roll back all relation cleanup.');
        } catch (RuntimeException $exception) {
            self::assertSame('Required collection handler failed.', $exception->getMessage());
        }
        self::assertNotNull($this->api->getItem($first));
        self::assertSame([$second, $first, $second], $this->api->getItem($source)->values['many']);
        self::assertSame('2000-01-01 00:00:00', Manager::table('collection_items')->where('id', $source)->value('updated_at'));
        $handler->failWithin = false;
        $handler->within = [];
        $deleter->execute($first);
        self::assertSame([$second, $second], $this->api->getItem($source)->values['many']);
        self::assertNull($this->api->getItem($first));
        self::assertSame([['saved', $source], ['deleted', $first]], array_map(static fn ($change): array => [$change->action, $change->id], $handler->within));
        self::assertNotSame('2000-01-01 00:00:00', Manager::table('collection_items')->where('id', $source)->value('updated_at'));
    }

    public function testClearNeedsFreshHttpConfirmationAndCountsContainNoPrivateNames(): void
    {
        $this->field(target: $this->target, settings: ['on_delete' => 'clear']);
        $target = $this->api->addItem($this->form($this->target, 'target'));
        $source = $this->api->addItem($this->form($this->source, 'private-source', (string) $target));
        $impact = $this->api->previewItemDeletion($target);
        self::assertSame(1, $impact->clearValues);
        self::assertSame(0, $impact->blockedValues);
        $user = FunctionalUserFactory::createSupervisor();
        $url = '/admin/collections/' . $this->target . '/items/' . $target . '/delete';
        $response = $this->handleRequest($url, cookies: $this->actingAs($user));
        $html = (string) $response->getContent();
        $dialog = HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelector('form[action="' . $url . '"]');
        self::assertStringNotContainsString('private-source', $dialog->textContent);
        self::assertStringContainsString($impact->revision, $html);
        self::assertSame(200, $this->postForm($url, [], $user)->getStatusCode());
        self::assertNotNull($this->api->getItem($target));
        $this->api->addItem($this->form($this->source, 'another-source', (string) $target));
        self::assertSame(200, $this->postForm($url, ['relation_revision' => $impact->revision], $user)->getStatusCode());
        self::assertSame($target, $this->api->getItem($source)->values['related']);
        self::assertSame(302, $this->postForm($url, ['relation_revision' => $this->api->previewItemDeletion($target)->revision], $user)->getStatusCode());
        self::assertNull($this->api->getItem($target));
        self::assertArrayNotHasKey('related', $this->api->getItem($source)->values);
    }

    public function testSourceViewsDoNotInvalidateDeletionConfirmationButContentChangesDo(): void
    {
        $this->field(target: $this->target, settings: ['on_delete' => 'clear']);
        $target = $this->api->addItem($this->form($this->target, 'target'));
        $source = $this->api->addItem($this->form($this->source, 'source', (string) $target));
        $before = $this->api->previewItemDeletion($target);
        $this->api->incrementViews($source);
        self::assertSame($before->revision, $this->api->previewItemDeletion($target)->revision);
        $this->api->patchItem($source, new CollectionItemPatchDTO(attributes: ['name' => 'Changed source']));
        self::assertNotSame($before->revision, $this->api->previewItemDeletion($target)->revision);
        try {
            $this->api->deleteItem(id: $target, expectedRelations: $before->revision);
            self::fail('Source edits must still invalidate the confirmation.');
        } catch (CollectionDeletionException $exception) {
            self::assertArrayHasKey('revision', $exception->errors);
            self::assertNotNull($this->api->getItem($target));
        }
        $current = $this->api->previewItemDeletion($target);
        $this->api->incrementViews($source);
        $this->api->deleteItem(id: $target, expectedRelations: $current->revision);
        self::assertNull($this->api->getItem($target));
        self::assertArrayNotHasKey('related', $this->api->getItem($source)->values);
    }

    public function testRequiredRelationsCannotUseClearAndDefaultsStillPreventDeletion(): void
    {
        foreach ([['on_delete' => 'cascade'], ['on_delete' => 'clear']] as $settings) {
            try {
                $this->schema->saveField(null, new CollectionFieldFormDTO(collectionId: $this->source, code: 'related', name: 'Related', type: FieldType::Relation, required: true, multiple: false, sort: 100, settings: ['target_collection_id' => $this->target] + $settings));
                self::fail('Required relations must not be silently cleared.');
            } catch (CollectionSchemaException) {
                self::assertSame([], $this->schema->getFields($this->source));
            }
        }
        $target = $this->api->addItem($this->form($this->target, 'default-target'));
        $this->field(target: $this->target, settings: ['on_delete' => 'clear', 'default' => (string) $target]);
        self::assertSame(1, $this->api->previewItemDeletion($target)->defaultFields);
        $this->expectException(CollectionDeletionException::class);
        $this->api->deleteItem($target);
    }

    public function testBranchRevisionIncludesIncomingClearRelationsAndRollbackPreservesThem(): void
    {
        $sections = $this->container()->get(CollectionSectionsApiInterface::class);
        $section = $sections->addSection(new CollectionSectionFormDTO($this->target, null, 'branch', 'Branch', null, true, 100));
        $target = $this->api->addItem($this->form(collection: $this->target, code: 'target', section: $section));
        $field = $this->field(target: $this->target, settings: ['on_delete' => 'clear']);
        $before = $sections->previewDeletion($this->target, $section);
        $source = $this->api->addItem($this->form($this->source, 'external', (string) $target));
        try {
            $sections->deleteSection(collectionId: $this->target, id: $section, mode: SectionDeletionMode::DeleteItems, expectedRevision: $before->revision);
            self::fail('New incoming relations require a fresh confirmation.');
        } catch (CollectionDeletionException $exception) {
            self::assertArrayHasKey('revision', $exception->errors);
        }
        $current = $sections->previewDeletion($this->target, $section);
        self::assertSame(1, $current->relations->clearValues);
        $sections->deleteSection(collectionId: $this->target, id: $section, mode: SectionDeletionMode::DeleteItems, expectedRevision: $current->revision);
        self::assertArrayNotHasKey('related', $this->api->getItem($source)->values);
        self::assertNull($this->api->getItem($target));
        self::assertSame($field, $this->schema->getFields($this->source)[0]->id);
    }

    public function testClearCannotModifyContentOfAnUnavailableOwner(): void
    {
        $this->field(target: $this->target, settings: ['on_delete' => 'clear']);
        $target = $this->api->addItem($this->form($this->target, 'target'));
        $source = $this->api->addItem($this->form($this->source, 'source', (string) $target));
        Manager::table('collections')->where('id', $this->source)->update(['owner_module' => 'missing/module']);
        try {
            $this->api->deleteItem($target);
            self::fail('Unavailable owners must not lose their required lifecycle hooks.');
        } catch (CollectionDeletionException $exception) {
            self::assertArrayHasKey('owner', $exception->errors);
        }
        self::assertNotNull($this->api->getItem($target));
        self::assertSame($target, $this->api->getItem($source)->values['related']);
    }

    /** @param array<string, mixed> $settings */
    private function field(int $target, ?int $id = null, array $settings = []): int
    {
        return $this->schema->saveField($id, new CollectionFieldFormDTO(collectionId: $this->source, code: 'related', name: 'Related', type: FieldType::Relation, required: false, multiple: false, sort: 100, settings: ['target_collection_id' => $target] + $settings));
    }

    private function form(int $collection, string $code, string $related = '', ?int $section = null): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO($collection, $section, $code, $code, true, null, null, 100, null, null, $collection === $this->source ? ['related' => $related] : []);
    }
}
