<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionFieldCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionFieldUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Tests\Support\CollectionItemWriterFactory;

final class SaveCollectionFieldUseCaseTest extends TestCase
{
    private function dto(): CollectionFieldFormDTO
    {
        return new CollectionFieldFormDTO(
            collectionId: 1,
            code: 'author',
            name: 'Author',
            type: FieldType::String_,
            required: false,
            multiple: false,
            sort: 100,
        );
    }

    public function testCreatesNewFieldWhenCodeIsFreeInCollection(): void
    {
        $repository = $this->createMock(ContentCollectionFieldRepositoryInterface::class);
        $repository->method('findByCode')->with(1, 'author')->willReturn(null);
        $created = new ContentCollectionField();
        $created->id = 1;
        $repository->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $attributes): bool {
                return $attributes['collection_id'] === 1
                    && $attributes['code'] === 'author'
                    && $attributes['type'] === 'string';
            }))
            ->willReturn($created);
        $repository->expects(self::never())->method('update');

        self::assertSame(1, (new SaveCollectionFieldUseCase($repository, CollectionItemWriterFactory::immediateTransaction(), $this->collections(), CollectionItemWriterFactory::itemRepository()))->execute(null, $this->dto()));
    }

    public function testUpdatesExistingField(): void
    {
        $existing = new ContentCollectionField(['collection_id' => 1, 'code' => 'author']);
        $existing->id = 7;

        $repository = $this->createMock(ContentCollectionFieldRepositoryInterface::class);
        $repository->method('findByCode')->willReturn($existing);
        $repository->method('findForUpdate')->with(7)->willReturn($existing);
        $repository->expects(self::once())->method('update')->with(7, self::anything());
        $repository->expects(self::never())->method('create');

        self::assertSame(7, (new SaveCollectionFieldUseCase($repository, CollectionItemWriterFactory::immediateTransaction(), $this->collections(), CollectionItemWriterFactory::itemRepository()))->execute(7, $this->dto()));
    }

    public function testThrowsWhenCodeBelongsToAnotherFieldOfCollection(): void
    {
        $other = new ContentCollectionField(['collection_id' => 1, 'code' => 'author']);
        $other->id = 3;

        $repository = $this->createMock(ContentCollectionFieldRepositoryInterface::class);
        $repository->method('findByCode')->willReturn($other);
        $repository->expects(self::never())->method('create');
        $repository->expects(self::never())->method('update');

        $this->expectException(CollectionFieldCodeAlreadyExistsException::class);

        (new SaveCollectionFieldUseCase($repository, CollectionItemWriterFactory::immediateTransaction(), $this->collections(), CollectionItemWriterFactory::itemRepository()))->execute(null, $this->dto());
    }
    private function collections(): ContentCollectionRepositoryInterface
    {
        $repository = $this->createMock(ContentCollectionRepositoryInterface::class);
        $repository->method('findForUpdate')->willReturn(new ContentCollection(['code' => 'schema', 'name' => 'Schema']));
        return $repository;
    }

}
