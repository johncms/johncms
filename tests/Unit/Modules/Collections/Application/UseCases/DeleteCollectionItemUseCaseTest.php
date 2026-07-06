<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionItemUseCase;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteCollectionItemUseCaseTest extends TestCase
{
    public function testDelegatesDeletionToRepository(): void
    {
        $repository = $this->createMock(ContentCollectionItemRepositoryInterface::class);
        $repository->expects(self::once())->method('delete')->with(11);

        (new DeleteCollectionItemUseCase($repository))->execute(11);
    }
}
