<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Application\DTO\CollectionItemListItemDTO;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionItemsUseCase;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ListCollectionItemsUseCaseTest extends TestCase
{
    public function testCountBuildsAdminQueryIncludingInactive(): void
    {
        $repository = $this->createMock(ContentCollectionItemRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('countItems')
            ->with(self::callback(static function (ContentCollectionItemQuery $query): bool {
                return $query->collectionId === 1 && $query->sectionId === 4 && $query->onlyActive === false;
            }))
            ->willReturn(7);

        self::assertSame(7, (new ListCollectionItemsUseCase($repository))->count(1, 4));
    }

    public function testGetPageMapsItemsToRowDtos(): void
    {
        $item = new ContentCollectionItem(['code' => 'hello', 'name' => 'Hello', 'active' => false, 'sort' => 100, 'section_id' => 4]);
        $item->id = 9;

        $repository = $this->createMock(ContentCollectionItemRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findItems')
            ->with(self::callback(static function (ContentCollectionItemQuery $query): bool {
                return $query->collectionId === 1 && $query->limit === 10 && $query->offset === 20;
            }))
            ->willReturn(new Collection([$item]));

        $rows = (new ListCollectionItemsUseCase($repository))->getPage(1, null, 10, 20);

        self::assertCount(1, $rows);
        self::assertInstanceOf(CollectionItemListItemDTO::class, $rows[0]);
        self::assertSame(9, $rows[0]->id);
        self::assertSame('hello', $rows[0]->code);
        self::assertFalse($rows[0]->active);
        self::assertSame(4, $rows[0]->sectionId);
    }
}
