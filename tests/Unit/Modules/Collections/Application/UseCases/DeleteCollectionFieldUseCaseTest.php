<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionFieldUseCase;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Tests\Support\CollectionItemWriterFactory;

final class DeleteCollectionFieldUseCaseTest extends TestCase
{
    public function testDelegatesDeletionToRepository(): void
    {
        $repository = $this->createMock(ContentCollectionFieldRepositoryInterface::class);
        $field = new ContentCollectionField(['collection_id' => 1, 'code' => 'field']);
        $repository->method('findById')->with(4)->willReturn($field);
        $repository->method('findForUpdate')->with(4)->willReturn($field);
        $repository->expects(self::once())->method('delete')->with(4);

        (new DeleteCollectionFieldUseCase($repository, CollectionItemWriterFactory::immediateTransaction(), $this->collections()))->execute(4);
    }
    private function collections(): ContentCollectionRepositoryInterface
    {
        $repository = $this->createMock(ContentCollectionRepositoryInterface::class);
        $repository->method('findForUpdate')->willReturn(new ContentCollection(['code' => 'schema', 'name' => 'Schema']));
        return $repository;
    }

}
