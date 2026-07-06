<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Domain\Query;

use Johncms\Modules\Collections\Domain\Enums\FilterOperator;
use Johncms\Modules\Collections\Domain\Enums\SortDirection;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Query\FieldFilterDTO;
use Johncms\Modules\Collections\Domain\Query\OrderByDTO;
use PHPUnit\Framework\TestCase;

final class ContentCollectionItemQueryTest extends TestCase
{
    public function testDefaults(): void
    {
        $query = new ContentCollectionItemQuery(collectionId: 5);

        self::assertSame(5, $query->collectionId);
        self::assertNull($query->sectionId);
        self::assertFalse($query->includeSubsections);
        self::assertSame([], $query->filters);
        self::assertTrue($query->onlyActive);
        self::assertSame([], $query->orderBy);
        self::assertNull($query->limit);
        self::assertSame(0, $query->offset);
    }

    public function testCarriesTypedFiltersAndOrdering(): void
    {
        $filter = new FieldFilterDTO('rating', FilterOperator::Gte, 3);
        $order = new OrderByDTO('sort', SortDirection::Desc);

        $query = new ContentCollectionItemQuery(
            collectionId: 1,
            sectionId: 10,
            includeSubsections: true,
            filters: [$filter],
            onlyActive: false,
            orderBy: [$order],
            limit: 20,
            offset: 40,
        );

        self::assertSame(10, $query->sectionId);
        self::assertTrue($query->includeSubsections);
        self::assertSame([$filter], $query->filters);
        self::assertSame('rating', $query->filters[0]->fieldCode);
        self::assertSame(FilterOperator::Gte, $query->filters[0]->operator);
        self::assertSame(3, $query->filters[0]->value);
        self::assertFalse($query->onlyActive);
        self::assertSame([$order], $query->orderBy);
        self::assertSame(SortDirection::Desc, $query->orderBy[0]->direction);
        self::assertSame(20, $query->limit);
        self::assertSame(40, $query->offset);
    }

    public function testOrderByDefaultsToAscending(): void
    {
        $order = new OrderByDTO('name');

        self::assertSame(SortDirection::Asc, $order->direction);
    }
}
