<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\DTO;

final readonly class PhotoDetailDTO
{
    public function __construct(
        public int $id,
        public int $userId,
        public int $albumId,
        public string $userName,
        public string $albumName,
        public string $picture,
        public string $previewPicture,
        public string $formattedDescription,
        public string $displayDate,
        public int $views,
        public int $downloads,
        public int $rating,
        public int $votePlus,
        public int $voteMinus,
        public int $commCount,
        public bool $canVote,
        public bool $canComment,
        public bool $canManage,
        public bool $isOwner,
        public string $userAlbumsUrl,
        public string $userAlbumUrl,
        public string $commentsUrl,
        public string $downloadUrl,
        public string $likeUrl,
        public string $dislikeUrl,
        public string $editUrl,
        public string $moveUrl,
        public string $deleteUrl,
        public string $addToProfileUrl,
    ) {
    }
}
