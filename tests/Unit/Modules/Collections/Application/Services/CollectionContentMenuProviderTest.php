<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Collections\Application\Services\CollectionContentMenuProvider;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Updates\SystemUpdateStateInterface;
use PHPUnit\Framework\TestCase;

final class CollectionContentMenuProviderTest extends TestCase
{
    /** Guests reach this provider too, so a pending schema must not cost them the page. */
    public function testPendingUpdateReadsNothingFromTheSchema(): void
    {
        $collections = $this->createMock(ContentCollectionRepositoryInterface::class);
        $collections->expects(self::never())->method('getForContentMenu');

        self::assertSame([], [...$this->provider($collections, true)->menuItems()]);
    }

    public function testFinishedUpdateListsTheCollections(): void
    {
        $collections = $this->createMock(ContentCollectionRepositoryInterface::class);
        $collections->expects(self::once())->method('getForContentMenu')->willReturn(new Collection());

        self::assertSame([], [...$this->provider($collections, false)->menuItems()]);
    }

    private function provider(ContentCollectionRepositoryInterface $collections, bool $updateRequired): CollectionContentMenuProvider
    {
        $access = $this->createStub(AccessCheckerInterface::class);
        $access->method('allows')->willReturn(false);
        $state = $this->createStub(SystemUpdateStateInterface::class);
        $state->method('isRequiredFor')->willReturn($updateRequired);

        return new CollectionContentMenuProvider($collections, new EnsureCollectionContentAccessUseCase($access, $collections), $state);
    }
}
