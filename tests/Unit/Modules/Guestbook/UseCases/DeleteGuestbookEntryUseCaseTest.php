<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\UseCases;

use Johncms\Files\FileStorage;
use Johncms\Modules\Guestbook\Application\Services\DeleteAttachedFilesService;
use Johncms\Modules\Guestbook\Application\UseCases\DeleteGuestbookEntryUseCase;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class DeleteGuestbookEntryUseCaseTest extends TestCase
{
    private GuestbookEntryRepositoryInterface&MockObject $repository;
    private FileStorage&MockObject $storage;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(GuestbookEntryRepositoryInterface::class);
        $this->storage = $this->createMock(FileStorage::class);
    }

    public function testDeletesAttachedFilesAndEntry(): void
    {
        $entry = new GuestbookEntry(['attached_files' => [10, 11]]);
        $entry->id = 3;

        $this->storage->expects(self::exactly(2))->method('delete')->with(self::logicalOr(10, 11));
        $this->repository->expects(self::once())->method('delete')->with(self::identicalTo($entry));

        $this->makeUseCase()->execute($entry);
    }

    public function testDeletesEntryWithoutFiles(): void
    {
        $entry = new GuestbookEntry(['attached_files' => []]);
        $entry->id = 4;

        $this->storage->expects(self::never())->method('delete');
        $this->repository->expects(self::once())->method('delete')->with(self::identicalTo($entry));

        $this->makeUseCase()->execute($entry);
    }

    private function makeUseCase(): DeleteGuestbookEntryUseCase
    {
        // DeleteAttachedFilesService — final, поэтому собираем реальный сервис с моками зависимостей
        $attachedFiles = new DeleteAttachedFilesService($this->storage, $this->createMock(LoggerInterface::class));

        return new DeleteGuestbookEntryUseCase($this->repository, $attachedFiles);
    }
}
