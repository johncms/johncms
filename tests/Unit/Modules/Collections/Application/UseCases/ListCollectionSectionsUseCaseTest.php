<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionSectionsUseCase;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ListCollectionSectionsUseCaseTest extends TestCase
{
    public function testTheTreeStandsEverySectionUnderItsParentWithTheItemsOfItsSubtree(): void
    {
        $repository = $this->createMock(ContentCollectionSectionRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('getAllWithItemCounts')
            ->with(1)
            ->willReturn(new Collection([
                $this->section(3, 2, 'Laravel', 100, 4),
                $this->section(1, null, 'Tech', 100, 2),
                $this->section(2, 1, 'PHP', 100, 7),
                $this->section(4, null, 'Life', 50, 0),
            ]));

        $tree = (new ListCollectionSectionsUseCase($repository))->getTree(1);

        self::assertSame([4, 1, 2, 3], array_map(static fn ($item): int => $item->section->id, $tree));
        self::assertSame([0, 0, 1, 2], array_map(static fn ($item): int => $item->depth, $tree));
        // Tech holds 2 of its own and 11 under it.
        self::assertSame(2, $tree[1]->items);
        self::assertSame(13, $tree[1]->itemsInSubtree);
    }

    private function section(int $id, ?int $parent, string $name, int $sort, int $items): ContentCollectionSection
    {
        $section = new ContentCollectionSection(['parent' => $parent, 'name' => $name, 'code' => strtolower($name), 'sort' => $sort, 'active' => true]);
        $section->id = $id;
        $section->setAttribute('items_count', $items);

        return $section;
    }
}
