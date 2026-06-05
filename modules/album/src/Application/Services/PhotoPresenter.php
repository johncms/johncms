<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Services;

use Johncms\Modules\Album\Application\DTO\PhotoViewDTO;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\System\Legacy\Tools;

/**
 * Builds presentation data for album photos (formerly the Albums\Photo accessors).
 *
 * URLs that target actions which are not migrated yet (show, list, comments, vote)
 * point to the legacy endpoints and will be updated as those actions are migrated.
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
            detailUrl: '/album/show?al=' . $photo->album_id . '&img=' . $photo->id . '&user=' . $photo->user_id . '&view=1',
            userAlbumsUrl: '/album/list?user=' . $photo->user_id,
            userAlbumUrl: '/album/show?al=' . $photo->album_id . '&user=' . $photo->user_id,
            commentsUrl: '/album/comments?img=' . $photo->id,
            likeUrl: '/album/vote?mod=plus&img=' . $photo->id,
            dislikeUrl: '/album/vote?mod=minus&img=' . $photo->id,
            votePlus: $photo->vote_plus,
            voteMinus: $photo->vote_minus,
            commCount: $photo->comm_count,
            views: $photo->views,
            canVote: $canVote,
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
