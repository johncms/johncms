<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\UseCases\CleanupOrphanForumFilesUseCase;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Storage\ForumAttachmentStorageInterface;
use PHPUnit\Framework\TestCase;

/**
 * An attachment no row points at is deleted once it is old enough; a file a row points at, a
 * fresh one that may belong to a post being saved, and what the directory holds for the web
 * server are left alone.
 */
final class CleanupOrphanForumFilesUseCaseTest extends TestCase
{
    public function testDeletesOnlyTheOldFilesNoRowPointsAt(): void
    {
        $day = 86400;
        $attachments = $this->attachments([
            'orphan.zip'  => time() - 2 * $day,
            'fresh.zip'   => time() - 60,
            'known.zip'   => time() - 2 * $day,
            'index.php'   => time() - 30 * $day,
            '.htaccess'   => time() - 30 * $day,
        ]);
        $files = $this->createMock(ForumFileRepositoryInterface::class);
        $files->method('filterKnownNames')->willReturnCallback(
            static fn (array $names): array => array_values(array_intersect($names, ['known.zip']))
        );

        $deleted = (new CleanupOrphanForumFilesUseCase($attachments, $files))->execute($day);

        self::assertSame(1, $deleted);
        self::assertSame(['fresh.zip', 'known.zip', 'index.php', '.htaccess'], $attachments->names());
    }

    /**
     * @param array<string, int> $files Names and the times they were written.
     */
    private function attachments(array $files): ForumAttachmentStorageInterface
    {
        return new class ($files) implements ForumAttachmentStorageInterface {
            /** @param array<string, int> $files */
            public function __construct(private array $files)
            {
            }

            public function path(string $filename): string
            {
                return 'forum/attach/' . $filename;
            }

            public function exists(string $filename): bool
            {
                return isset($this->files[$filename]);
            }

            public function url(string $filename): string
            {
                return '';
            }

            public function size(string $filename): int
            {
                return 0;
            }

            public function isImage(string $filename): bool
            {
                return false;
            }

            public function storeFile(string $filename, string $localPath): void
            {
                $this->files[$filename] = time();
            }

            public function delete(string $filename): void
            {
                unset($this->files[$filename]);
            }

            public function names(): array
            {
                return array_keys($this->files);
            }

            public function lastModified(string $filename): int
            {
                return $this->files[$filename];
            }

            public function withLocalCopy(string $filename, callable $handler): mixed
            {
                return $handler($filename);
            }
        };
    }
}
