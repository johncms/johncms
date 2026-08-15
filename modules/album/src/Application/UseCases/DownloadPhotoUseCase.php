<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoFileMissingException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Users\User;

final readonly class DownloadPhotoUseCase
{
    public function __construct(
        private AlbumPhotoRepositoryInterface $photoRepository,
        private EnsureAlbumAccessUseCase $ensureAccess,
        private User $currentUser,
    ) {
    }

    /**
     * Resolve the public URL of the photo file, counting a unique download.
     *
     * @return string the file URL to redirect to
     *
     * @throws AlbumPhotoNotFoundException     when the photo or its album is missing
     * @throws AlbumPhotoFileMissingException  when the source file is absent on disk
     * @throws \Johncms\Modules\Album\Application\Exceptions\AlbumAccessDeniedException
     * @throws \Johncms\Modules\Album\Application\Exceptions\AlbumPasswordRequiredException
     */
    public function execute(int $photoId): string
    {
        $photo = $this->photoRepository->findById($photoId);
        if ($photo === null || $photo->album === null) {
            throw new AlbumPhotoNotFoundException();
        }

        $this->ensureAccess->execute($photo->album);

        $path = UPLOAD_PATH . 'users/album/' . $photo->user_id . '/' . $photo->img_name;
        if (! is_file($path)) {
            throw new AlbumPhotoFileMissingException();
        }

        if (! $this->photoRepository->hasUserDownload($this->currentUser->id, $photo->id)) {
            $this->photoRepository->addDownload($this->currentUser->id, $photo->id, time());
            $this->photoRepository->refreshDownloadsCount($photo->id);
        }

        return pathToUrl($path);
    }
}
