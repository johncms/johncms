<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\Api;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionsApi;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionItemUseCase;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;

final class CollectionsApiTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private CollectionsApi $api;
    private int $collectionId;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('collections');

        $fieldRepository = new ContentCollectionFieldRepository();
        $itemRepository = new ContentCollectionItemRepository($fieldRepository, new ContentCollectionItemQueryCompiler());
        $collectionRepository = new ContentCollectionRepository();

        $writer = \Tests\Support\CollectionItemWriterFactory::create();
        $this->api = new CollectionsApi(
            $collectionRepository,
            $itemRepository,
            $writer,
            new DeleteCollectionItemUseCase($itemRepository),
            new \Johncms\Modules\Collections\Application\UseCases\PatchCollectionItemUseCase(
                $itemRepository, $collectionRepository, $fieldRepository,
                new \Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction(), $writer
            ),
            new \Johncms\Modules\Collections\Application\Services\CollectionApiMapper(),
        );

        $now = date('Y-m-d H:i:s');
        $this->collectionId = Capsule::table('collections')->insertGetId([
            'code' => 'blog', 'name' => 'Blog', 'sort' => 100, 'active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $fieldRepository->create(['collection_id' => $this->collectionId, 'code' => 'author', 'name' => 'Author', 'type' => 'string', 'required' => false, 'multiple' => false, 'sort' => 100]);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    /**
     * @param array<string, string|list<string>> $values
     */
    private function dto(string $code, array $values = []): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO(
            collectionId: $this->collectionId,
            sectionId: null,
            code: $code,
            name: 'Hello ' . $code,
            active: true,
            activeFrom: null,
            activeTo: null,
            sort: 100,
            previewText: null,
            detailText: null,
            values: $values,
        );
    }

    public function testFindCollectionResolvesByCode(): void
    {
        self::assertSame($this->collectionId, $this->api->findCollection('blog')?->id);
        self::assertNull($this->api->findCollection('missing'));
    }

    public function testAddItemReturnsIdAndPersistsValues(): void
    {
        $id = $this->api->addItem($this->dto('hello', ['author' => 'Alice']));

        $item = $this->api->getItem($id);
        self::assertNotNull($item);
        self::assertSame('hello', $item->code);
        self::assertSame('Alice', $item->values['author']);
    }

    public function testAddItemRejectsDuplicateCode(): void
    {
        $this->api->addItem($this->dto('hello'));

        $this->expectException(CollectionItemCodeAlreadyExistsException::class);
        $this->api->addItem($this->dto('hello'));
    }

    public function testUpdateItemRebuildsValues(): void
    {
        $id = $this->api->addItem($this->dto('hello', ['author' => 'Alice']));

        $this->api->updateItem($id, $this->dto('hello', ['author' => 'Bob']));

        self::assertSame('Bob', $this->api->getItem($id)?->values['author']);
    }

    public function testDeleteItemRemovesItAndValues(): void
    {
        $id = $this->api->addItem($this->dto('hello', ['author' => 'Alice']));

        $this->api->deleteItem($id);

        self::assertNull($this->api->getItem($id));
        self::assertSame(0, Capsule::table('collection_item_values')->where('item_id', $id)->count());
    }

    public function testGetItemsAndCountHonorQuery(): void
    {
        $this->api->addItem($this->dto('one'));
        $this->api->addItem($this->dto('two'));

        $query = new ContentCollectionItemQuery(collectionId: $this->collectionId);

        self::assertSame(2, $this->api->countItems($query));
        self::assertCount(2, $this->api->getItems($query));
    }
    public function testResultDatesAndValuesAreTypedAndReadingDoesNotRecordViews(): void
    {
        (new ContentCollectionFieldRepository())->create(['collection_id' => $this->collectionId, 'code' => 'day', 'name' => 'Day', 'type' => 'date']);
        $id = $this->api->addItem($this->dto('typed', ['day' => '2026-11-02']));
        $item = $this->api->getItem($id);
        self::assertInstanceOf(\DateTimeImmutable::class, $item->createdAt);
        self::assertInstanceOf(\DateTimeImmutable::class, $item->values['day']);
        self::assertSame('2026-11-02', $item->values['day']->format('Y-m-d'));
        self::assertSame(0, $item->viewCount);
        self::assertSame(0, $this->api->getItemByCode($this->collectionId, null, 'typed')->viewCount);
    }

    public function testPatchPreservesAbsentPropertiesAndReplacesOrClearsExplicitValues(): void
    {
        $fields = new ContentCollectionFieldRepository();
        $fields->create(['collection_id' => $this->collectionId, 'code' => 'tag', 'name' => 'Tags', 'type' => 'string', 'multiple' => true]);
        $id = $this->api->addItem($this->dto('hello', ['author' => 'Alice', 'tag' => ['php', 'cms']]), 7);
        $this->api->patchItem($id, new \Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO(
            attributes: ['name' => 'Changed'], values: ['tag' => ['news']]
        ), 8);
        $item = $this->api->getItem($id);
        self::assertSame('Changed', $item->name);
        self::assertSame('hello', $item->code);
        self::assertSame('Alice', $item->values['author']);
        self::assertSame(['news'], $item->values['tag']);
        self::assertSame(7, $item->createdBy);
        self::assertSame(8, $item->updatedBy);
        $this->api->patchItem($id, new \Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO(values: ['author' => null, 'tag' => null]));
        self::assertSame([], $this->api->getItem($id)->values);
    }

    public function testPatchCannotChangeCollectionOrClearARequiredProperty(): void
    {
        $id = $this->api->addItem($this->dto('hello', ['author' => 'Alice']));
        try {
            $this->api->patchItem($id, new \Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO(attributes: ['collectionId' => 999]));
            self::fail('Collection changes must not be patched.');
        } catch (\Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException) {
            self::assertSame('Alice', $this->api->getItem($id)->values['author']);
        }
        (new ContentCollectionFieldRepository())->findByCode($this->collectionId, 'author')->update(['required' => true]);
        $this->expectException(\Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException::class);
        $this->api->patchItem($id, new \Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO(values: ['author' => null]));
    }

    public function testPageLoadsSelectedValuesWithoutPerItemQueries(): void
    {
        for ($i = 0; $i < 12; ++$i) {
            $this->api->addItem($this->dto('item-' . $i, ['author' => 'Alice']));
        }
        Capsule::connection()->enableQueryLog();
        try {
            $this->api->getItems(new ContentCollectionItemQuery($this->collectionId, limit: 1));
            $singleQueries = count(Capsule::connection()->getQueryLog());
            Capsule::connection()->flushQueryLog();
            $items = $this->api->getItems(new ContentCollectionItemQuery($this->collectionId, fields: ['author']));
            foreach ($items as $item) {
                self::assertSame('Alice', $item->values['author']);
            }
            self::assertSame($singleQueries, count(Capsule::connection()->getQueryLog()));
            $page = $this->api->getPage(new ContentCollectionItemQuery($this->collectionId, limit: 2, offset: 2, fields: []));
            self::assertSame(12, $page->total);
            self::assertCount(2, $page->items);
            self::assertSame([], $page->items[0]->values);
        } finally {
            Capsule::connection()->disableQueryLog();
            Capsule::connection()->flushQueryLog();
        }
    }

    public function testBaseAndCustomFieldsWithTheSameNameRemainIndependent(): void
    {
        (new ContentCollectionFieldRepository())->create(['collection_id' => $this->collectionId, 'code' => 'name', 'name' => 'Name', 'type' => 'string']);
        $id = $this->api->addItem($this->dto('hello', ['name' => 'Custom']));
        $query = new ContentCollectionItemQuery($this->collectionId, filters: [
            new \Johncms\Modules\Collections\Domain\Query\FieldFilterDTO('item.name', \Johncms\Modules\Collections\Domain\Enums\FilterOperator::Eq, 'Hello hello'),
            new \Johncms\Modules\Collections\Domain\Query\FieldFilterDTO('field.name', \Johncms\Modules\Collections\Domain\Enums\FilterOperator::Eq, 'Custom'),
        ]);
        self::assertSame($id, $this->api->getItems($query)[0]->id);
    }

    public function testViewsAreAtomicAndDoNotChangeEditingMetadata(): void
    {
        $id = $this->api->addItem($this->dto('hello'), 7);
        Capsule::table('collection_items')->where('id', $id)->update(['updated_at' => '2000-01-01 00:00:00']);
        $this->api->incrementViews($id);
        $this->api->incrementViews($id);
        $item = $this->api->getItem($id);
        self::assertSame(2, $item->viewCount);
        self::assertSame('2000-01-01 00:00:00', $item->updatedAt->format('Y-m-d H:i:s'));
        self::assertSame(7, $item->createdBy);
        $this->api->patchItem($id, new \Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO(attributes: ['active' => false]));
        $this->expectException(\Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException::class);
        $this->api->incrementViews($id);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badReferences')]
    public function testInvalidQueryNeverSilentlyBroadensSelection(string $field, mixed $value, \Johncms\Modules\Collections\Domain\Enums\FilterOperator $operator): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->api->getItems(new ContentCollectionItemQuery($this->collectionId, filters: [
            new \Johncms\Modules\Collections\Domain\Query\FieldFilterDTO($field, $operator, $value),
        ]));
    }

    public static function badReferences(): array
    {
        $eq = \Johncms\Modules\Collections\Domain\Enums\FilterOperator::Eq;
        return [['name', 'Hello', $eq], ['item.missing', 1, $eq], ['field.missing', 'x', $eq],
            ['item.id', '1', $eq], ['item.active', 1, $eq], ['item.id', 1, \Johncms\Modules\Collections\Domain\Enums\FilterOperator::Like],
            ['item.id', 1, \Johncms\Modules\Collections\Domain\Enums\FilterOperator::In]];
    }

    public function testUnknownOrderingAndSelectionFailExplicitly(): void
    {
        try {
            $this->api->countItems(new ContentCollectionItemQuery($this->collectionId, orderBy: [new \Johncms\Modules\Collections\Domain\Query\OrderByDTO('item.missing')]));
            self::fail('Unknown ordering must fail even when counting.');
        } catch (\InvalidArgumentException) {
            $this->expectException(\InvalidArgumentException::class);
            $this->api->getItems(new ContentCollectionItemQuery($this->collectionId, fields: ['missing']));
        }
    }

    public function testClearedBooleanIsNotReintroducedByAnUnrelatedPatch(): void
    {
        (new ContentCollectionFieldRepository())->create(['collection_id' => $this->collectionId, 'code' => 'flag', 'name' => 'Flag', 'type' => 'boolean']);
        $id = $this->api->addItem($this->dto('hello', ['flag' => '0']));
        $this->api->patchItem($id, new \Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO(values: ['flag' => null]));
        $this->api->patchItem($id, new \Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO(attributes: ['name' => 'Changed']));
        self::assertArrayNotHasKey('flag', $this->api->getItem($id)->values);
    }

    public function testExplicitOrderingHasAnIdTieBreakerAcrossPages(): void
    {
        $ids = [];
        foreach (['a', 'b', 'c'] as $code) {
            $ids[] = $id = $this->api->addItem($this->dto($code));
            $this->api->patchItem($id, new \Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO(attributes: ['name' => 'Same']));
        }
        $actual = [];
        for ($offset = 0; $offset < 3; ++$offset) {
            $page = $this->api->getPage(new ContentCollectionItemQuery($this->collectionId,
                orderBy: [new \Johncms\Modules\Collections\Domain\Query\OrderByDTO('item.name')], limit: 1, offset: $offset));
            $actual[] = $page->items[0]->id;
        }
        self::assertSame($ids, $actual);
    }

    public function testScheduledViewsAreRejectedAndDateFiltersUseTypedValues(): void
    {
        $form = new CollectionItemFormDTO(...array_replace(get_object_vars($this->dto('future')), ['activeFrom' => '2999-01-01 00:00:00']));
        $id = $this->api->addItem($form);
        try {
            $this->api->incrementViews($id);
            self::fail('A scheduled item must not collect views.');
        } catch (\Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException) {
            self::assertSame(0, $this->api->getItem($id)->viewCount);
        }
        $query = new ContentCollectionItemQuery($this->collectionId, onlyActive: false, filters: [
            new \Johncms\Modules\Collections\Domain\Query\FieldFilterDTO('item.active_from', \Johncms\Modules\Collections\Domain\Enums\FilterOperator::Gte, new \DateTimeImmutable('2999-01-01 00:00:00Z')),
        ]);
        self::assertSame($id, $this->api->getItems($query)[0]->id);
    }

    public function testTypedCustomDatesUseStoredUtcRegardlessOfPhpDefaultTimezone(): void
    {
        (new ContentCollectionFieldRepository())->create(['collection_id' => $this->collectionId, 'code' => 'moment', 'name' => 'Moment', 'type' => 'datetime']);
        $previous = date_default_timezone_get();
        try {
            date_default_timezone_set('Europe/Moscow');
            $id = $this->api->addItem($this->dto('moment', ['moment' => '2001-02-03T12:30']));
            $date = $this->api->getItem($id)->values['moment'];
            self::assertInstanceOf(\DateTimeImmutable::class, $date);
            self::assertSame('2001-02-03 12:30:00', $date->format('Y-m-d H:i:s'));
            self::assertSame('UTC', $date->getTimezone()->getName());
        } finally {
            date_default_timezone_set($previous);
        }
    }

}
