<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Online\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Online\Application\DTO\OnlineItemDTO;
use Johncms\Modules\Online\Application\UseCases\GetUsersHistoryUseCase;
use Johncms\Modules\Online\Domain\Repository\OnlineUserRepositoryInterface;
use Johncms\Utils\DateFormatterInterface;
use Johncms\Users\UserPlaceFormatterInterface;
use Johncms\Users\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Markup;

final class GetUsersHistoryUseCaseTest extends TestCase
{
    private OnlineUserRepositoryInterface&MockObject $repository;
    private DateFormatterInterface&MockObject $dateFormatter;
    private UserPlaceFormatterInterface&MockObject $placeFormatter;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(OnlineUserRepositoryInterface::class);
        $this->dateFormatter = $this->createMock(DateFormatterInterface::class);
        $this->placeFormatter = $this->createMock(UserPlaceFormatterInterface::class);
    }

    public function testCountDelegatesToHistory(): void
    {
        $this->repository->expects(self::once())->method('countHistory')->willReturn(3);

        self::assertSame(3, $this->makeUseCase()->count());
    }

    public function testGetPagePassesSlicingToHistory(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('getHistory')
            ->with(10, 20)
            ->willReturn(new Collection());

        self::assertCount(0, $this->makeUseCase()->getPage(10, 20, null));
    }

    public function testGetPageUsesDisplayDateForHistory(): void
    {
        $user = new User(['id' => 7, 'name' => 'Bob', 'place' => '/some/place', 'sestime' => 100, 'browser' => 'UA']);

        $this->repository->method('getHistory')->willReturn(new Collection([$user]));
        $this->placeFormatter->method('format')
            ->willReturnCallback(static fn (?string $place): Markup => new Markup((string) $place, 'UTF-8'));
        $this->dateFormatter->expects(self::once())->method('format')->with(100)->willReturn('DD');

        $result = $this->makeUseCase()->getPage(10, 0, null);

        self::assertInstanceOf(OnlineItemDTO::class, $result[0]);
        self::assertSame('/some/place', (string) $result[0]->placeName);
        self::assertSame('DD', $result[0]->displayDate);
    }

    private function makeUseCase(): GetUsersHistoryUseCase
    {
        return new GetUsersHistoryUseCase($this->repository, $this->dateFormatter, $this->placeFormatter);
    }
}
