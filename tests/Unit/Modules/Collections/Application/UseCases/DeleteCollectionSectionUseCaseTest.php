<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionSectionUseCase;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteCollectionSectionUseCaseTest extends TestCase
{
    public function testDelegatesDeletionToRepository(): void
    {
        $repository = $this->createMock(ContentCollectionSectionRepositoryInterface::class);
        $repository->expects(self::once())->method('delete')->with(6);

        $section = new \Johncms\Modules\Collections\Domain\Models\ContentCollectionSection(['collection_id' => 1]);
        $section->id = 6;
        $repository->method('getAllForUpdate')->willReturn(new \Illuminate\Database\Eloquent\Collection([$section]));
        $repository->method('findForUpdate')->with(6)->willReturn($section);
        $collections = $this->createMock(\Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface::class);
        $collections->method('findForUpdate')->with(1)->willReturn(new \Johncms\Modules\Collections\Domain\Models\ContentCollection());
        $transaction = $this->createMock(\Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface::class);
        $transaction->method('run')->willReturnCallback(static fn (callable $operation) => $operation());
        (new DeleteCollectionSectionUseCase($repository, $collections, $transaction))->execute(6, 1);
    }
}
