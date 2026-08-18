<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\UseCases;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Files\FileStore;
use Johncms\Modules\Guestbook\Application\UseCases\ClearGuestbookUseCase;
use Johncms\Modules\Guestbook\Domain\Enums\ClearGuestbookPeriod;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use Johncms\Storage\StorageInterface;
use Johncms\Storage\StorageRegistryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tests\Unit\Files\InMemoryFileRepository;

final class ClearGuestbookUseCaseTest extends TestCase
{
    private GuestbookEntryRepositoryInterface&MockObject $repository;
    private InMemoryFileRepository $files;
    private StorageInterface&MockObject $disk;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(GuestbookEntryRepositoryInterface::class);
        $this->files = new InMemoryFileRepository();
        $this->disk = $this->createMock(StorageInterface::class);
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

        $this->files->add(10, 'guestbook/aa/bb/cc/first.jpg');
        $this->files->add(11, 'guestbook/aa/bb/cc/second.jpg');
        $this->disk->expects(self::exactly(2))->method('delete');

        $this->makeUseCase()->execute(false, ClearGuestbookPeriod::All);

        self::assertSame([], $this->files->ids());
    }

    private function makeUseCase(): ClearGuestbookUseCase
    {
        $registry = $this->createMock(StorageRegistryInterface::class);
        $registry->method('disk')->willReturn($this->disk);

        $store = new FileStore($registry, $this->files, $this->createMock(LoggerInterface::class));

        return new ClearGuestbookUseCase($this->repository, $store);
    }
}
