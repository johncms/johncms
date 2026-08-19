<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Infrastructure\Persistence\Repository;

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Domain\Enums\FilterOperator;
use Johncms\Modules\Collections\Domain\Enums\SortDirection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Query\FieldFilterDTO;
use Johncms\Modules\Collections\Domain\Query\OrderByDTO;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;

final class ContentCollectionItemRepositoryTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private ContentCollectionItemRepository $repository;

    private int $collectionId;
    private int $sectionA;
    private int $sectionB;
    private int $itemAHigh;
    private int $itemALow;
    private int $itemBActive;
    private int $authorFieldId;
    private int $ratingFieldId;
    private int $tagFieldId;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('collections');

        $this->repository = new ContentCollectionItemRepository(
            new ContentCollectionFieldRepository(),
            new ContentCollectionItemQueryCompiler(),
        );

        $this->seed();
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testFindItemsReturnsOnlyActiveWithinPublishWindowByDefault(): void
    {
        $query = new ContentCollectionItemQuery(collectionId: $this->collectionId);

        $codes = $this->repository->findItems($query)->pluck('code')->all();

        // Inactive, future and expired items are excluded; ordered by sort asc, id asc.
        self::assertSame(['a-low', 'a-high', 'b-active'], $codes);
    }

    public function testCountItemsMatchesFindItems(): void
    {
        $query = new ContentCollectionItemQuery(collectionId: $this->collectionId);

        self::assertSame(3, $this->repository->countItems($query));
        self::assertCount(3, $this->repository->findItems($query));
    }

    public function testFilterBySection(): void
    {
        $query = new ContentCollectionItemQuery(collectionId: $this->collectionId, sectionId: $this->sectionA);

        $codes = $this->repository->findItems($query)->pluck('code')->all();

        self::assertSame(['a-low', 'a-high'], $codes);
        self::assertSame(2, $this->repository->countItems($query));
    }

    public function testOnlyActiveFalseReturnsEveryItemInSection(): void
    {
        $query = new ContentCollectionItemQuery(
            collectionId: $this->collectionId,
            sectionId: $this->sectionA,
            onlyActive: false,
        );

        self::assertSame(5, $this->repository->countItems($query));
    }

    public function testOrderBySortDescending(): void
    {
        $query = new ContentCollectionItemQuery(
            collectionId: $this->collectionId,
            sectionId: $this->sectionA,
            orderBy: [new OrderByDTO('sort', SortDirection::Desc)],
        );

        $codes = $this->repository->findItems($query)->pluck('code')->all();

        self::assertSame(['a-high', 'a-low'], $codes);
    }

    public function testLimitAndOffsetSlicing(): void
    {
        $query = new ContentCollectionItemQuery(collectionId: $this->collectionId, limit: 1, offset: 1);

        $codes = $this->repository->findItems($query)->pluck('code')->all();

        // Full active set is [a-low, a-high, b-active]; offset 1, limit 1 -> a-high.
        self::assertSame(['a-high'], $codes);
    }

    public function testFindByIdAndByCode(): void
    {
        $byId = $this->repository->findById($this->itemAHigh);
        self::assertNotNull($byId);
        self::assertSame('a-high', $byId->code);

        $byCode = $this->repository->findByCode($this->collectionId, $this->sectionA, 'a-low');
        self::assertNotNull($byCode);
        self::assertSame($this->itemALow, $byCode->id);

        self::assertNull($this->repository->findByCode($this->collectionId, $this->sectionA, 'missing'));
    }

    public function testFilterByCustomIntegerFieldGte(): void
    {
        $query = new ContentCollectionItemQuery(
            collectionId: $this->collectionId,
            filters: [new FieldFilterDTO('rating', FilterOperator::Gte, 5)],
        );

        $codes = $this->repository->findItems($query)->pluck('code')->all();

        // rating: a-high=1 (excluded), a-low=9, b-active=5; ordered by sort asc.
        self::assertSame(['a-low', 'b-active'], $codes);
        self::assertSame(2, $this->repository->countItems($query));
    }

    public function testFilterByCustomStringFieldEq(): void
    {
        $query = new ContentCollectionItemQuery(
            collectionId: $this->collectionId,
            filters: [new FieldFilterDTO('author', FilterOperator::Eq, 'Bob')],
        );

        $codes = $this->repository->findItems($query)->pluck('code')->all();

        self::assertSame(['a-low'], $codes);
    }

    public function testFilterByMultipleFieldMatchesAnyValue(): void
    {
        $query = new ContentCollectionItemQuery(
            collectionId: $this->collectionId,
            filters: [new FieldFilterDTO('tag', FilterOperator::Eq, 'php')],
        );

        $codes = $this->repository->findItems($query)->pluck('code')->all();

        // Only a-high carries the 'php' tag among its multiple tag values.
        self::assertSame(['a-high'], $codes);
    }

    public function testFilterInOperatorOnCustomField(): void
    {
        $query = new ContentCollectionItemQuery(
            collectionId: $this->collectionId,
            filters: [new FieldFilterDTO('rating', FilterOperator::In, [1, 5])],
        );

        $codes = $this->repository->findItems($query)->pluck('code')->all();

        // rating in (1,5): a-high=1, b-active=5; both sort=100, tie broken by id.
        self::assertSame(['a-high', 'b-active'], $codes);
    }

    public function testOrderByCustomIntegerField(): void
    {
        $asc = new ContentCollectionItemQuery(
            collectionId: $this->collectionId,
            sectionId: $this->sectionA,
            orderBy: [new OrderByDTO('rating', SortDirection::Asc)],
        );
        $desc = new ContentCollectionItemQuery(
            collectionId: $this->collectionId,
            sectionId: $this->sectionA,
            orderBy: [new OrderByDTO('rating', SortDirection::Desc)],
        );

        // Section A active items: a-high (rating 1), a-low (rating 9). Custom sort
        // differs from the base sort order (which would place a-low first).
        self::assertSame(['a-high', 'a-low'], $this->repository->findItems($asc)->pluck('code')->all());
        self::assertSame(['a-low', 'a-high'], $this->repository->findItems($desc)->pluck('code')->all());
    }

    public function testGetValuesMapReturnsCastValuesGroupedByField(): void
    {
        $item = ContentCollectionItem::query()->with('values.field')->find($this->itemAHigh);
        self::assertNotNull($item);

        $map = $item->getValuesMap();

        self::assertSame('Alice', $map['author']);
        self::assertSame(1, $map['rating']);           // integer field cast to int
        self::assertSame(['php', 'sql'], $map['tag']); // multiple field ordered by sort
    }

    private function seed(): void
    {
        $now = Carbon::now();

        $this->collectionId = Capsule::table('collections')->insertGetId([
            'code' => 'blog', 'name' => 'Blog', 'sort' => 100, 'active' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $this->authorFieldId = $this->insertField('author', 'string', false);
        $this->ratingFieldId = $this->insertField('rating', 'integer', false);
        $this->tagFieldId = $this->insertField('tag', 'string', true);

        $this->sectionA = $this->insertSection('tech');
        $this->sectionB = $this->insertSection('news');

        $this->itemAHigh = $this->insertItem($this->sectionA, 'a-high', ['sort' => 100]);
        $this->itemALow = $this->insertItem($this->sectionA, 'a-low', ['sort' => 50]);
        $this->insertItem($this->sectionA, 'a-inactive', ['sort' => 10, 'active' => 0]);
        $this->insertItem($this->sectionA, 'a-future', ['active_from' => $now->copy()->addDay()]);
        $this->insertItem($this->sectionA, 'a-expired', ['active_to' => $now->copy()->subDay()]);
        $this->itemBActive = $this->insertItem($this->sectionB, 'b-active', ['sort' => 100]);

        // EAV values for the active items.
        $this->insertValue($this->itemAHigh, $this->authorFieldId, ['value_string' => 'Alice']);
        $this->insertValue($this->itemAHigh, $this->ratingFieldId, ['value_int' => 1]);
        $this->insertValue($this->itemAHigh, $this->tagFieldId, ['value_string' => 'php'], 0);
        $this->insertValue($this->itemAHigh, $this->tagFieldId, ['value_string' => 'sql'], 1);

        $this->insertValue($this->itemALow, $this->authorFieldId, ['value_string' => 'Bob']);
        $this->insertValue($this->itemALow, $this->ratingFieldId, ['value_int' => 9]);
        $this->insertValue($this->itemALow, $this->tagFieldId, ['value_string' => 'go']);

        $this->insertValue($this->itemBActive, $this->ratingFieldId, ['value_int' => 5]);
    }

    private function insertField(string $code, string $type, bool $multiple): int
    {
        $now = Carbon::now();

        return Capsule::table('collection_fields')->insertGetId([
            'collection_id' => $this->collectionId,
            'code'          => $code,
            'name'          => $code,
            'type'          => $type,
            'required'      => 0,
            'multiple'      => $multiple ? 1 : 0,
            'sort'          => 100,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }

    private function insertSection(string $code): int
    {
        $now = Carbon::now();

        return Capsule::table('collection_sections')->insertGetId([
            'collection_id' => $this->collectionId,
            'parent'        => null,
            'name'          => $code,
            'code'          => $code,
            'active'        => 1,
            'sort'          => 100,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function insertItem(int $sectionId, string $code, array $overrides = []): int
    {
        $now = Carbon::now();

        return Capsule::table('collection_items')->insertGetId(array_merge([
            'collection_id' => $this->collectionId,
            'section_id'    => $sectionId,
            'name'          => $code,
            'code'          => $code,
            'active'        => 1,
            'active_from'   => null,
            'active_to'     => null,
            'sort'          => 100,
            'view_count'    => 0,
            'created_at'    => $now,
            'updated_at'    => $now,
        ], $overrides));
    }

    /**
     * @param array<string, mixed> $columns typed value column(s) to set
     */
    private function insertValue(int $itemId, int $fieldId, array $columns, int $sort = 0): void
    {
        Capsule::table('collection_item_values')->insert(array_merge([
            'item_id'      => $itemId,
            'field_id'     => $fieldId,
            'value_string' => null,
            'value_int'    => null,
            'value_double' => null,
            'value_date'   => null,
            'value_text'   => null,
            'sort'         => $sort,
        ], $columns));
    }
}
