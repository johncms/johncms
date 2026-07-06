<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionUseCase;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteCollectionUseCaseTest extends TestCase
{
    public function testDelegatesDeletionToRepository(): void
    {
        $repository = $this->createMock(ContentCollectionRepositoryInterface::class);
        $repository->expects(self::once())->method('delete')->with(9);

        (new DeleteCollectionUseCase($repository))->execute(9);
    }
}
