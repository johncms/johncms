<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\UseCases;

use Johncms\Files\FileStore;
use Johncms\Modules\Guestbook\Application\UseCases\DeleteGuestbookEntryUseCase;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use Johncms\Storage\StorageInterface;
use Johncms\Storage\StorageRegistryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tests\Unit\Files\InMemoryFileRepository;

final class DeleteGuestbookEntryUseCaseTest extends TestCase
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

    public function testDeletesAttachedFilesAndEntry(): void
    {
        $this->files->add(10, 'guestbook/aa/bb/cc/first.jpg');
        $this->files->add(11, 'guestbook/aa/bb/cc/second.jpg');

        $entry = new GuestbookEntry(['attached_files' => [10, 11]]);
        $entry->id = 3;

        $this->disk->expects(self::exactly(2))->method('delete');
        $this->repository->expects(self::once())->method('delete')->with(self::identicalTo($entry));

        $this->makeUseCase()->execute($entry);

        self::assertSame([], $this->files->ids());
    }

    public function testDeletesEntryWithoutFiles(): void
    {
        $entry = new GuestbookEntry(['attached_files' => []]);
        $entry->id = 4;

        $this->disk->expects(self::never())->method('delete');
        $this->repository->expects(self::once())->method('delete')->with(self::identicalTo($entry));

        $this->makeUseCase()->execute($entry);
    }

    private function makeUseCase(): DeleteGuestbookEntryUseCase
    {
        $registry = $this->createMock(StorageRegistryInterface::class);
        $registry->method('disk')->willReturn($this->disk);

        $store = new FileStore($registry, $this->files, $this->createMock(LoggerInterface::class));

        return new DeleteGuestbookEntryUseCase($this->repository, $store);
    }
}
