<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Services;

use Johncms\Modules\Album\Application\DTO\PhotoDetailDTO;
use Johncms\Modules\Album\Application\DTO\PhotoViewDTO;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\System\Legacy\Tools;

/**
 * Builds presentation data for album photos (formerly the Albums\Photo accessors).
 */
final readonly class PhotoPresenter
{
    private const PREVIEW_TEXT_LIMIT = 100;

    public function __construct(
        private Tools $tools,
    ) {
    }

    public function present(AlbumPhoto $photo, bool $canVote): PhotoViewDTO
    {
        return new PhotoViewDTO(
            id: $photo->id,
            userName: $photo->user->name ?? '',
            albumName: $photo->album->name ?? '',
            previewText: $this->previewText($photo->description),
            previewPicture: $this->picture($photo->user_id, $photo->tmb_name),
            detailUrl: '/album/photo/' . $photo->id,
            userAlbumsUrl: '/album/user/' . $photo->user_id,
            userAlbumUrl: '/album/' . $photo->album_id,
            commentsUrl: '/album/photo/' . $photo->id . '/comments',
            likeUrl: '/album/photo/' . $photo->id . '/vote/plus',
            dislikeUrl: '/album/photo/' . $photo->id . '/vote/minus',
            votePlus: $photo->vote_plus,
            voteMinus: $photo->vote_minus,
            commCount: $photo->comm_count,
            views: $photo->views,
            canVote: $canVote,
        );
    }

    public function presentDetail(
        AlbumPhoto $photo,
        bool $canVote,
        bool $canComment,
        bool $canManage,
        bool $isOwner,
    ): PhotoDetailDTO {
        return new PhotoDetailDTO(
            id: $photo->id,
            userId: $photo->user_id,
            albumId: $photo->album_id,
            userName: $photo->user->name ?? '',
            albumName: $this->tools->checkout($photo->album->name ?? ''),
            picture: $this->picture($photo->user_id, $photo->img_name),
            previewPicture: $this->picture($photo->user_id, $photo->tmb_name),
            formattedDescription: $this->tools->smilies($this->tools->checkout($photo->description, 1, 0)),
            displayDate: $this->tools->displayDate($photo->time),
            views: $photo->views,
            downloads: $photo->downloads,
            rating: $photo->rating,
            votePlus: $photo->vote_plus,
            voteMinus: $photo->vote_minus,
            commCount: $photo->comm_count,
            canVote: $canVote,
            canComment: $canComment,
            canManage: $canManage,
            isOwner: $isOwner,
            userAlbumsUrl: '/album/user/' . $photo->user_id,
            userAlbumUrl: '/album/' . $photo->album_id,
            commentsUrl: '/album/photo/' . $photo->id . '/comments',
            downloadUrl: '/album/photo/' . $photo->id . '/download',
            likeUrl: '/album/photo/' . $photo->id . '/vote/plus',
            dislikeUrl: '/album/photo/' . $photo->id . '/vote/minus',
            editUrl: '/album/photo/' . $photo->id . '/edit',
            moveUrl: '/album/photo/' . $photo->id . '/move',
            deleteUrl: '/album/photo/' . $photo->id . '/delete',
            addToProfileUrl: '/album/photo/' . $photo->id . '?profile=1',
        );
    }

    private function previewText(string $description): string
    {
        $text = $this->tools->checkout($description, 0, 0);
        if (mb_strlen($text) > self::PREVIEW_TEXT_LIMIT) {
            $text = mb_substr($text, 0, self::PREVIEW_TEXT_LIMIT - 3) . '...';
        }

        return $text;
    }

    private function picture(int $userId, string $thumbName): string
    {
        $path = UPLOAD_PATH . 'users/album/' . $userId . '/' . $thumbName;
        if (is_file($path)) {
            return pathToUrl($path);
        }

        return '';
    }
}
