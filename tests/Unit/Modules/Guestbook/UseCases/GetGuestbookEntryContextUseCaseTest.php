<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\UseCases;

use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookEntryNotFoundException;
use Johncms\Modules\Guestbook\Application\UseCases\GetGuestbookEntryContextUseCase;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetGuestbookEntryContextUseCaseTest extends TestCase
{
    public function testReturnsEntryWhenFound(): void
    {
        $entry = new GuestbookEntry();

        $repository = $this->createMock(GuestbookEntryRepositoryInterface::class);
        $repository->expects(self::once())->method('find')->with(10)->willReturn($entry);

        $useCase = new GetGuestbookEntryContextUseCase($repository);

        self::assertSame($entry, $useCase->execute(10));
    }

    public function testThrowsWhenEntryNotFound(): void
    {
        $repository = $this->createMock(GuestbookEntryRepositoryInterface::class);
        $repository->method('find')->willReturn(null);

        $useCase = new GetGuestbookEntryContextUseCase($repository);

        $this->expectException(GuestbookEntryNotFoundException::class);
        $useCase->execute(99);
    }
}
