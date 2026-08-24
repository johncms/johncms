<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\DTO;

final readonly class PhotoViewDTO
{
    public function __construct(
        public int $id,
        public string $userName,
        public string $albumName,
        public string $previewText,
        public string $previewPicture,
        public string $detailUrl,
        public string $userAlbumsUrl,
        public string $userAlbumUrl,
        public string $commentsUrl,
        public string $likeUrl,
        public string $dislikeUrl,
        public int $votePlus,
        public int $voteMinus,
        public int $commCount,
        public int $views,
        public bool $canVote,
    ) {
    }
}
