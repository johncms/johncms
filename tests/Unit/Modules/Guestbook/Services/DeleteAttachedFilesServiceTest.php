<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\Services;

use Johncms\Files\FileStorage;
use Johncms\Modules\Guestbook\Application\Services\DeleteAttachedFilesService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class DeleteAttachedFilesServiceTest extends TestCase
{
    public function testDeletesOnlyValidIntegerIds(): void
    {
        $storage = $this->createMock(FileStorage::class);
        $deleted = [];
        $storage->method('delete')->willReturnCallback(function (int $id) use (&$deleted) {
            $deleted[] = $id;
        });

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $service = new DeleteAttachedFilesService($storage, $logger);
        $service->delete([3, '7', 'abc', null, '']);

        self::assertSame([3, 7], $deleted);
    }

    public function testLogsAndContinuesWhenStorageThrows(): void
    {
        $exception = new RuntimeException('storage failure');

        $storage = $this->createMock(FileStorage::class);
        $deleted = [];
        $storage->method('delete')->willReturnCallback(function (int $id) use (&$deleted, $exception) {
            if ($id === 1) {
                throw $exception;
            }
            $deleted[] = $id;
        });

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('error')
            ->with('storage failure', [
                'exception' => $exception,
                'file'      => 1,
                'post_id'   => 42,
            ]);

        $service = new DeleteAttachedFilesService($storage, $logger);
        $service->delete([1, 2], 42);

        self::assertSame([2], $deleted);
    }
}
