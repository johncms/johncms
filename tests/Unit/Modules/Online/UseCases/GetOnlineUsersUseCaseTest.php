<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Online\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Online\Application\DTO\OnlineItemDTO;
use Johncms\Modules\Online\Application\UseCases\GetOnlineUsersUseCase;
use Johncms\Modules\Online\Domain\Repository\OnlineUserRepositoryInterface;
use Johncms\Users\UserPlaceFormatterInterface;
use Johncms\Users\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetOnlineUsersUseCaseTest extends TestCase
{
    private OnlineUserRepositoryInterface&MockObject $repository;
    private UserPlaceFormatterInterface&MockObject $placeFormatter;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(OnlineUserRepositoryInterface::class);
        $this->placeFormatter = $this->createMock(UserPlaceFormatterInterface::class);
    }

    public function testCountDelegatesToRepository(): void
    {
        $this->repository->expects(self::once())->method('countOnline')->willReturn(7);

        self::assertSame(7, $this->makeUseCase()->count());
    }

    public function testGetPagePassesSlicingToRepository(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('getOnline')
            ->with(10, 20)
            ->willReturn(new Collection());

        self::assertCount(0, $this->makeUseCase()->getPage(10, 20, null));
    }

    public function testGetPageMapsUserToDtoWithoutFormatter(): void
    {
        $user = new User(['id' => 42, 'name' => 'Bob', 'place' => '/some/place', 'movings' => 5, 'sestime' => 100, 'browser' => 'UA']);

        $this->repository->method('getOnline')->willReturn(new Collection([$user]));
        $this->placeFormatter->method('format')->willReturnArgument(0);

        $result = $this->makeUseCase()->getPage(10, 0, null);

        self::assertInstanceOf(OnlineItemDTO::class, $result[0]);
        self::assertSame(42, $result[0]->id);
        self::assertSame('Bob', $result[0]->name);
        self::assertSame('/profile/42', $result[0]->profileUrl);
        self::assertSame('/some/place', $result[0]->placeName);
        self::assertStringStartsWith('5 - ', $result[0]->displayDate);
    }

    private function makeUseCase(): GetOnlineUsersUseCase
    {
        return new GetOnlineUsersUseCase($this->repository, $this->placeFormatter);
    }
}
