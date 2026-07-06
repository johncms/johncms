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

        (new DeleteCollectionSectionUseCase($repository))->execute(6);
    }
}
