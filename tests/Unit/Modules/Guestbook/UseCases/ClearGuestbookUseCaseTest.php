<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Files\FileStorage;
use Johncms\Modules\Guestbook\Application\Services\DeleteAttachedFilesService;
use Johncms\Modules\Guestbook\Application\UseCases\ClearGuestbookUseCase;
use Johncms\Modules\Guestbook\Domain\Enums\ClearGuestbookPeriod;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ClearGuestbookUseCaseTest extends TestCase
{
    private GuestbookEntryRepositoryInterface&MockObject $repository;
    private FileStorage&MockObject $storage;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(GuestbookEntryRepositoryInterface::class);
        $this->storage = $this->createMock(FileStorage::class);
    }

    public function testCutoffIsCalculatedFromPeriod(): void
    {
        $capturedCutoffs = [];

        $this->repository
            ->method('getEntriesToClear')
            ->willReturnCallback(function (bool $adminClub, ?int $olderThan) use (&$capturedCutoffs) {
                $capturedCutoffs['get'] = [$adminClub, $olderThan];
                return new Collection();
            });
        $this->repository
            ->expects(self::once())
            ->method('deleteEntries')
            ->willReturnCallback(function (bool $adminClub, ?int $olderThan) use (&$capturedCutoffs) {
                $capturedCutoffs['delete'] = [$adminClub, $olderThan];
            });

        $this->makeUseCase()->execute(true, ClearGuestbookPeriod::OlderThanWeek);

        self::assertTrue($capturedCutoffs['get'][0]);
        self::assertEqualsWithDelta(time() - 604800, $capturedCutoffs['get'][1], 5);
        self::assertSame($capturedCutoffs['get'], $capturedCutoffs['delete']);
    }

    public function testAllPeriodPassesNullCutoff(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('getEntriesToClear')
            ->with(false, null)
            ->willReturn(new Collection());
        $this->repository->expects(self::once())->method('deleteEntries')->with(false, null);

        $this->makeUseCase()->execute(false, ClearGuestbookPeriod::All);
    }

    public function testDeletesAttachedFilesBeforeClearing(): void
    {
        $withFiles = new GuestbookEntry(['attached_files' => [10, 11]]);
        $withFiles->id = 1;
        $withoutFiles = new GuestbookEntry(['attached_files' => []]);
        $withoutFiles->id = 2;

        $this->repository
            ->method('getEntriesToClear')
            ->willReturn(new Collection([$withFiles, $withoutFiles]));
        $this->repository->expects(self::once())->method('deleteEntries');

        $this->storage->expects(self::exactly(2))->method('delete')->with(self::logicalOr(10, 11));

        $this->makeUseCase()->execute(false, ClearGuestbookPeriod::All);
    }

    private function makeUseCase(): ClearGuestbookUseCase
    {
        $attachedFiles = new DeleteAttachedFilesService($this->storage, $this->createMock(LoggerInterface::class));

        return new ClearGuestbookUseCase($this->repository, $attachedFiles);
    }
}
