<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Application\DTO\PublicItemDTO;
use Johncms\Modules\Collections\Application\Services\ItemContentFormatter;
use Johncms\Modules\Collections\Application\UseCases\ListPublicItemsUseCase;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Security\HtmlSanitizerInterface;
use PHPUnit\Framework\TestCase;

final class ListPublicItemsUseCaseTest extends TestCase
{
    private function useCase(ContentCollectionItemRepositoryInterface $repository): ListPublicItemsUseCase
    {
        // Passthrough sanitizer: these tests assert mapping, not sanitization.
        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->method('sanitize')->willReturnArgument(0);

        return new ListPublicItemsUseCase($repository, new ItemContentFormatter($sanitizer));
    }

    public function testCountUsesOnlyActiveQuery(): void
    {
        $repository = $this->createMock(ContentCollectionItemRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('countItems')
            ->with(self::callback(static function (ContentCollectionItemQuery $query): bool {
                return $query->collectionId === 1 && $query->sectionId === 2 && $query->onlyActive === true;
            }))
            ->willReturn(4);

        self::assertSame(4, $this->useCase($repository)->count(1, 2));
    }

    public function testGetPageMapsToPublicDto(): void
    {
        $item = new ContentCollectionItem(['code' => 'hello', 'name' => 'Hello', 'preview_text' => 'Intro', 'section_id' => 7]);
        $item->id = 3;

        $repository = $this->createMock(ContentCollectionItemRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findItems')
            ->with(self::callback(static function (ContentCollectionItemQuery $query): bool {
                return $query->onlyActive === true && $query->limit === 10 && $query->offset === 0;
            }))
            ->willReturn(new Collection([$item]));

        $rows = $this->useCase($repository)->getPage(1, null, 10, 0);

        self::assertCount(1, $rows);
        self::assertInstanceOf(PublicItemDTO::class, $rows[0]);
        self::assertSame('hello', $rows[0]->code);
        self::assertSame('Hello', $rows[0]->name);
        // The formatter hands the sanitized text over as markup.
        self::assertSame('Intro', (string) $rows[0]->previewText);
        self::assertSame(7, $rows[0]->sectionId);
    }
}
