<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Application\DTO\CollectionListItemDTO;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionsUseCase;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ListCollectionsUseCaseTest extends TestCase
{
    public function testCountDelegatesToRepository(): void
    {
        $repository = $this->createMock(ContentCollectionRepositoryInterface::class);
        $repository->expects(self::once())->method('countAll')->willReturn(5);

        self::assertSame(5, (new ListCollectionsUseCase($repository))->count());
    }

    public function testGetPageMapsModelsToRowDtos(): void
    {
        $withSections = new ContentCollection([
            'code' => 'blog', 'name' => 'Blog', 'active' => true, 'sort' => 100,
            'settings' => ['has_sections' => true],
        ]);
        $withSections->id = 7;

        $flat = new ContentCollection(['code' => 'pages', 'name' => 'Pages', 'active' => false, 'sort' => 200]);
        $flat->id = 8;

        $repository = $this->createMock(ContentCollectionRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('getAll')
            ->with(10, 20)
            ->willReturn(new Collection([$withSections, $flat]));

        $rows = (new ListCollectionsUseCase($repository))->getPage(10, 20);

        self::assertCount(2, $rows);
        self::assertInstanceOf(CollectionListItemDTO::class, $rows[0]);
        self::assertSame(7, $rows[0]->id);
        self::assertSame('blog', $rows[0]->code);
        self::assertSame('Blog', $rows[0]->name);
        self::assertTrue($rows[0]->active);
        self::assertSame(100, $rows[0]->sort);
        self::assertTrue($rows[0]->hasSections);

        self::assertFalse($rows[1]->active);
        self::assertFalse($rows[1]->hasSections);
    }
}
