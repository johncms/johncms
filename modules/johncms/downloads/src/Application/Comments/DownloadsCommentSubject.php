<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Comments;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Comments\CommentSubjectDTO;
use Johncms\Comments\CommentSubjectInterface;
use Johncms\Modules\Downloads\Application\Services\CategoryNavService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;

/**
 * A file of the downloads, discussed through the comments of the core.
 *
 * The discussion is there while the comments of the downloads are switched on, or for those who
 * see them anyway; a file awaiting moderation is discussed by the moderators only. The file has no
 * owner who manages its comments: only the moderator deletes and edits them.
 */
final readonly class DownloadsCommentSubject implements CommentSubjectInterface
{
    public const string TYPE = 'downloads';

    public function __construct(
        private AccessCheckerInterface $access,
        private DownloadFileRepositoryInterface $files,
        private CategoryNavService $categoryNav,
        private DownloadFilePathService $paths,
    ) {
    }

    public function type(): string
    {
        return self::TYPE;
    }

    public function find(int $id): ?CommentSubjectDTO
    {
        if (! config('johncms.mod_down_comm') && ! $this->access->allows(DownloadsPermissions::COMMENTS_ALWAYS_VIEW)) {
            return null;
        }

        $file = $this->files->findFile($id);
        if ($file === null || ! is_file($file->dir . '/' . $file->name)) {
            return null;
        }
        if ($file->type === 3 && ! $this->access->allows(DownloadsPermissions::MODERATE)) {
            return null;
        }

        $url = $this->paths->getFileUrl($file);

        return new CommentSubjectDTO(
            type: self::TYPE,
            id: $file->id,
            title: (string) $file->rus_name,
            url: $url,
            discussionUrl: '/downloads/comments/' . $file->id,
            breadcrumbs: [
                ['name' => d__('downloads', 'Downloads'), 'url' => '/downloads/'],
                ...$this->categoryNav->crumbsForFileDir((string) $file->dir),
                ['name' => (string) $file->rus_name, 'url' => $url],
            ],
        );
    }

    public function commentsChanged(int $id, int $delta): void
    {
        $this->files->changeCommentCount($id, $delta);
    }

    public function discussionViewed(CommentSubjectDTO $subject): void
    {
        // Nothing is marked as unread for the owner of a file.
    }
}
