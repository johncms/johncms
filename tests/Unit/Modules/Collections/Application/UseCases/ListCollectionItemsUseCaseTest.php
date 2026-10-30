<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionItemsUseCase;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Services\SectionTree;
use Johncms\Modules\Collections\Domain\ValueObjects\ItemListFilter;
use PHPUnit\Framework\TestCase;

final class ListCollectionItemsUseCaseTest extends TestCase
{
    public function testASectionNarrowsTheItemsToItsSubtree(): void
    {
        $repository = $this->createMock(ContentCollectionItemRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('countForPanel')
            ->with(1, self::anything(), [2, 3, 4], self::anything())
            ->willReturn(5);

        $tree = new SectionTree([2 => 0, 3 => 2, 4 => 3, 5 => 0]);

        self::assertSame(5, $this->useCase($repository)->count(1, new ItemListFilter(section: 2), $tree));
    }

    public function testTheItemsWithoutASectionAndAnyAreToldApart(): void
    {
        $repository = $this->createMock(ContentCollectionItemRepositoryInterface::class);
        $repository->expects(self::exactly(2))
            ->method('getForPanel')
            ->with(1, self::anything(), self::logicalOr(self::identicalTo([ItemListFilter::WITHOUT_SECTION]), self::isNull()), self::anything(), 10, 20)
            ->willReturn(new Collection());

        $tree = new SectionTree([2 => 0]);
        $useCase = $this->useCase($repository);
        $useCase->getPage(1, new ItemListFilter(section: ItemListFilter::WITHOUT_SECTION), $tree, 10, 20);
        $useCase->getPage(1, new ItemListFilter(), $tree, 10, 20);
    }

    public function testOnlyTheFieldsMarkedForTheListBecomeColumns(): void
    {
        $listed = new ContentCollectionField(['code' => 'price', 'in_list' => true]);
        $hidden = new ContentCollectionField(['code' => 'body', 'in_list' => false]);
        $fields = $this->createMock(ContentCollectionFieldRepositoryInterface::class);
        $fields->method('getByCollection')->with(1)->willReturn(new Collection([$listed, $hidden]));

        $useCase = new ListCollectionItemsUseCase(
            $this->createMock(ContentCollectionItemRepositoryInterface::class),
            $this->createMock(ContentCollectionSectionRepositoryInterface::class),
            $fields,
        );

        self::assertSame([$listed], $useCase->listedFields(1));
    }

    private function useCase(ContentCollectionItemRepositoryInterface $repository): ListCollectionItemsUseCase
    {
        return new ListCollectionItemsUseCase(
            $repository,
            $this->createMock(ContentCollectionSectionRepositoryInterface::class),
            $this->createMock(ContentCollectionFieldRepositoryInterface::class),
        );
    }
}
