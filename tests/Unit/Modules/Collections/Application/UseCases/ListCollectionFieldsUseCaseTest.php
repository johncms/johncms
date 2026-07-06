<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldListItemDTO;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionFieldsUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ListCollectionFieldsUseCaseTest extends TestCase
{
    public function testMapsFieldsToRowDtos(): void
    {
        $field = new ContentCollectionField([
            'code'     => 'author',
            'name'     => 'Author',
            'type'     => 'string',
            'required' => true,
            'multiple' => false,
            'sort'     => 100,
        ]);
        $field->id = 5;

        $repository = $this->createMock(ContentCollectionFieldRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('getByCollection')
            ->with(1)
            ->willReturn(new Collection([$field]));

        $rows = (new ListCollectionFieldsUseCase($repository))->getByCollection(1);

        self::assertCount(1, $rows);
        self::assertInstanceOf(CollectionFieldListItemDTO::class, $rows[0]);
        self::assertSame(5, $rows[0]->id);
        self::assertSame('author', $rows[0]->code);
        self::assertSame(FieldType::String_, $rows[0]->type);
        self::assertTrue($rows[0]->required);
        self::assertFalse($rows[0]->multiple);
        self::assertSame(100, $rows[0]->sort);
    }
}
