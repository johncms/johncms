<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionListItemDTO;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionSectionsUseCase;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ListCollectionSectionsUseCaseTest extends TestCase
{
    public function testCountDelegatesToRepositoryWithParent(): void
    {
        $repository = $this->createMock(ContentCollectionSectionRepositoryInterface::class);
        $repository->expects(self::once())->method('countByCollection')->with(1, 5)->willReturn(3);

        self::assertSame(3, (new ListCollectionSectionsUseCase($repository))->count(1, 5));
    }

    public function testGetPageMapsSectionsWithChildCount(): void
    {
        $section = new ContentCollectionSection(['code' => 'tech', 'name' => 'Technology', 'active' => true, 'sort' => 100]);
        $section->id = 4;
        $section->child_sections_count = 2;

        $repository = $this->createMock(ContentCollectionSectionRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('getByCollection')
            ->with(1, null, 10, 0)
            ->willReturn(new Collection([$section]));

        $rows = (new ListCollectionSectionsUseCase($repository))->getPage(1, null, 10, 0);

        self::assertCount(1, $rows);
        self::assertInstanceOf(CollectionSectionListItemDTO::class, $rows[0]);
        self::assertSame(4, $rows[0]->id);
        self::assertSame('tech', $rows[0]->code);
        self::assertSame('Technology', $rows[0]->name);
        self::assertTrue($rows[0]->active);
        self::assertSame(100, $rows[0]->sort);
        self::assertSame(2, $rows[0]->childCount);
    }
}
