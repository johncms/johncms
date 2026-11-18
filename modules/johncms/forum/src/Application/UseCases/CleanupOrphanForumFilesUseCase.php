<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Storage\ForumAttachmentStorageInterface;
use Johncms\Storage\StorageException;

/**
 * Deletes the attachments no row of `cms_forum_files` points at: what a process left behind when
 * it died between writing a file and committing the post. A file younger than the age given may
 * belong to a post being saved right now, and is left alone.
 */
final readonly class CleanupOrphanForumFilesUseCase
{
    /** What the directory holds for the web server, not for a post. */
    private const array KEPT = ['index.php', '.htaccess'];

    public function __construct(
        private ForumAttachmentStorageInterface $attachments,
        private ForumFileRepositoryInterface $files,
    ) {
    }

    /**
     * @return int How many files were deleted.
     * @throws StorageException
     */
    public function execute(int $minAgeSeconds): int
    {
        $names = array_values(array_filter(
            $this->attachments->names(),
            static fn (string $name): bool => ! in_array($name, self::KEPT, true) && ! str_starts_with($name, '.')
        ));
        $orphans = array_diff($names, $this->files->filterKnownNames($names));

        $before = time() - $minAgeSeconds;
        $deleted = 0;
        foreach ($orphans as $name) {
            if ($this->attachments->lastModified($name) > $before) {
                continue;
            }

            $this->attachments->delete($name);
            $deleted++;
        }

        return $deleted;
    }
}
