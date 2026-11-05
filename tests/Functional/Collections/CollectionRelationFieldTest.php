<?php

declare(strict_types=1);

namespace Tests\Functional\Collections;

use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSchemaApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Enums\SectionDeletionMode;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

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
        $this->schema->saveField(null, new CollectionFieldFormDTO($this->source, 'many', 'Many', FieldType::Relation, false, true, 100, settings: ['target_collection_id' => $this->target]));
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
                $this->schema->saveField(null, new CollectionFieldFormDTO($this->source, 'related', 'Related', FieldType::Relation, false, false, 100, settings: $settings));
                self::fail('A relation requires an existing target collection.');
            } catch (CollectionSchemaException) {
                self::assertSame([], $this->schema->getFields($this->source));
            }
        }
        $target = $this->api->addItem($this->form($this->target, 'target'));
        $field = $this->field($this->target, settings: ['default' => (string) $target]);
        $source = $this->api->addItem(new CollectionItemFormDTO($this->source, null, 'default', 'Default', true, null, null, 100, null, null, []));
        self::assertSame($target, $this->api->getItem($source)->values['related']);
        try {
            $this->field($this->source, $field, ['default' => null]);
            self::fail('Filled relations require explicit conversion.');
        } catch (CollectionSchemaException) {
            self::assertSame($this->target, $this->schema->getFields($this->source)[0]->settings['target_collection_id']);
        }
        $this->api->deleteItem($source);
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
        $first = $this->api->addItem($this->form($this->source, 'first', section: $section));
        $second = $this->api->addItem($this->form($this->source, 'second', (string) $first, $section));
        $this->api->patchItem($first, new CollectionItemPatchDTO(values: ['related' => (string) $second]));
        $sections->deleteSection($this->source, $section, SectionDeletionMode::DeleteItems);
        self::assertNull($this->api->getItem($first));
        self::assertNull($this->api->getItem($second));
    }

    /** @param array<string, mixed> $settings */
    private function field(int $target, ?int $id = null, array $settings = []): int
    {
        return $this->schema->saveField($id, new CollectionFieldFormDTO($this->source, 'related', 'Related', FieldType::Relation, false, false, 100, settings: ['target_collection_id' => $target] + $settings));
    }

    private function form(int $collection, string $code, string $related = '', ?int $section = null): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO($collection, $section, $code, $code, true, null, null, 100, null, null, $collection === $this->source ? ['related' => $related] : []);
    }
}
