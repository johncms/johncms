<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSectionCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionSectionUseCase;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class SaveCollectionSectionUseCaseTest extends TestCase
{
    private function dto(?int $parent = null): CollectionSectionFormDTO
    {
        return new CollectionSectionFormDTO(
            collectionId: 1,
            parent: $parent,
            code: 'tech',
            name: 'Technology',
            description: null,
            active: true,
            sort: 100,
        );
    }

    public function testCreatesNewSectionWhenCodeIsFreeUnderParent(): void
    {
        $repository = $this->createMock(ContentCollectionSectionRepositoryInterface::class);
        $repository->method('findByCode')->with(1, 5, 'tech')->willReturn(null);
        $repository->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $attributes): bool {
                return $attributes['collection_id'] === 1
                    && $attributes['parent'] === 5
                    && $attributes['code'] === 'tech';
            }))
            ->willReturn(new ContentCollectionSection());
        $repository->expects(self::never())->method('update');

        self::assertFalse((new SaveCollectionSectionUseCase($repository))->execute(null, $this->dto(5)));
    }

    public function testUpdatesExistingSection(): void
    {
        $existing = new ContentCollectionSection(['collection_id' => 1, 'code' => 'tech']);
        $existing->id = 7;

        $repository = $this->createMock(ContentCollectionSectionRepositoryInterface::class);
        $repository->method('findByCode')->willReturn($existing);
        $repository->method('findById')->with(7)->willReturn($existing);
        $repository->expects(self::once())->method('update')->with(7, self::anything());
        $repository->expects(self::never())->method('create');

        self::assertTrue((new SaveCollectionSectionUseCase($repository))->execute(7, $this->dto()));
    }

    public function testThrowsWhenCodeBelongsToAnotherSibling(): void
    {
        $other = new ContentCollectionSection(['collection_id' => 1, 'code' => 'tech']);
        $other->id = 3;

        $repository = $this->createMock(ContentCollectionSectionRepositoryInterface::class);
        $repository->method('findByCode')->willReturn($other);
        $repository->expects(self::never())->method('create');
        $repository->expects(self::never())->method('update');

        $this->expectException(CollectionSectionCodeAlreadyExistsException::class);

        (new SaveCollectionSectionUseCase($repository))->execute(null, $this->dto());
    }
}
