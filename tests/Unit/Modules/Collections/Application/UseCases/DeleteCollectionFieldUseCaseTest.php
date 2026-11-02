<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionFieldUseCase;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteCollectionFieldUseCaseTest extends TestCase
{
    public function testDelegatesDeletionToRepository(): void
    {
        $repository = $this->createMock(ContentCollectionFieldRepositoryInterface::class);
        $field = new \Johncms\Modules\Collections\Domain\Models\ContentCollectionField(['collection_id' => 1, 'code' => 'field']);
        $repository->method('findById')->with(4)->willReturn($field);
        $repository->expects(self::once())->method('delete')->with(4);

        (new DeleteCollectionFieldUseCase($repository, \Tests\Support\CollectionItemWriterFactory::immediateTransaction()))->execute(4);
    }
}
