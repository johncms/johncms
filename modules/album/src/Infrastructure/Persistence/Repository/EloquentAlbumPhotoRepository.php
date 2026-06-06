<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Johncms\Modules\Album\Domain\Enums\AlbumAccess;
use Johncms\Modules\Album\Domain\Enums\TopFilter;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;

final class EloquentAlbumPhotoRepository implements AlbumPhotoRepositoryInterface
{
    private const NEW_PHOTO_PERIOD = 259200; // 3 days
    private const RECENT_COMMENTS_PERIOD = 86400; // 24 hours

    public function countNewPublicSince(int $time): int
    {
        return AlbumPhoto::query()
            ->where('time', '>', $time)
            ->where('access', AlbumAccess::Public->value)
            ->count();
    }

    public function findById(int $photoId): ?AlbumPhoto
    {
        return AlbumPhoto::query()->with(['album', 'user'])->find($photoId);
    }

    public function countByAlbum(int $albumId): int
    {
        return AlbumPhoto::query()->where('album_id', $albumId)->count();
    }

    public function countPhotosAfter(int $albumId, int $photoId): int
    {
        return AlbumPhoto::query()
            ->where('album_id', $albumId)
            ->where('id', '>', $photoId)
            ->count();
    }

    public function paginatePhotosByAlbum(int $albumId, int $page, int $perPage): LengthAwarePaginator
    {
        return AlbumPhoto::query()
            ->with(['album', 'user'])
            ->where('album_id', $albumId)
            ->orderByDesc('id')
            ->paginate(perPage: $perPage, page: $page);
    }

    public function getPhotoByAlbumOffset(int $albumId, int $offset): ?AlbumPhoto
    {
        return AlbumPhoto::query()
            ->with(['album', 'user'])
            ->where('album_id', $albumId)
            ->orderByDesc('id')
            ->offset(max(0, $offset))
            ->limit(1)
            ->first();
    }

    public function hasUserView(int $userId, int $photoId): bool
    {
        return Capsule::table('cms_album_views')
            ->where('user_id', $userId)
            ->where('file_id', $photoId)
            ->exists();
    }

    public function addView(int $userId, int $photoId, int $time): void
    {
        Capsule::table('cms_album_views')->insert([
            'user_id' => $userId,
            'file_id' => $photoId,
            'time'    => $time,
        ]);
    }

    public function refreshViewsCount(int $photoId): void
    {
        $views = Capsule::table('cms_album_views')->where('file_id', $photoId)->count();
        AlbumPhoto::query()->where('id', $photoId)->update(['views' => $views]);
    }

    public function hasUserDownload(int $userId, int $photoId): bool
    {
        return Capsule::table('cms_album_downloads')
            ->where('user_id', $userId)
            ->where('file_id', $photoId)
            ->exists();
    }

    public function addDownload(int $userId, int $photoId, int $time): void
    {
        Capsule::table('cms_album_downloads')->insert([
            'user_id' => $userId,
            'file_id' => $photoId,
            'time'    => $time,
        ]);
    }

    public function refreshDownloadsCount(int $photoId): void
    {
        $downloads = Capsule::table('cms_album_downloads')->where('file_id', $photoId)->count();
        AlbumPhoto::query()->where('id', $photoId)->update(['downloads' => $downloads]);
    }

    public function paginateTop(
        TopFilter $filter,
        ?int $restrictToPublicForUser,
        int $currentUserId,
        int $page,
        int $perPage
    ): LengthAwarePaginator {
        $query = AlbumPhoto::query()
            ->with(['album', 'user'])
            ->select('cms_album_files.*');

        $this->applyTopFilter($query, $filter, $currentUserId);

        if (! $filter->isOwnerScoped() && $restrictToPublicForUser !== null) {
            $query->where(static function (Builder $builder) use ($restrictToPublicForUser): void {
                $builder
                    ->where('cms_album_files.access', AlbumAccess::Public->value)
                    ->orWhere('cms_album_files.user_id', $restrictToPublicForUser);
            });
        }

        return $query->paginate(perPage: $perPage, page: $page);
    }

    private function applyTopFilter(Builder $query, TopFilter $filter, int $currentUserId): void
    {
        match ($filter) {
            TopFilter::New => $query
                ->where('cms_album_files.time', '>', time() - self::NEW_PHOTO_PERIOD)
                ->orderByDesc('cms_album_files.time'),
            TopFilter::Views => $query
                ->where('cms_album_files.views', '>', 0)
                ->orderByDesc('cms_album_files.views')
                ->orderByDesc('cms_album_files.downloads'),
            TopFilter::Downloads => $query
                ->where('cms_album_files.downloads', '>', 0)
                ->orderByDesc('cms_album_files.downloads')
                ->orderByDesc('cms_album_files.views'),
            TopFilter::Comments => $query
                ->where('cms_album_files.comm_count', '>', 0)
                ->orderByDesc('cms_album_files.comm_count')
                ->orderByDesc('cms_album_files.views'),
            TopFilter::Votes => $query
                ->whereRaw('(vote_plus - vote_minus) > 2')
                ->orderByRaw('(vote_plus - vote_minus) DESC')
                ->orderByDesc('cms_album_files.views'),
            TopFilter::Worst => $query
                ->whereRaw('(vote_plus - vote_minus) < -2')
                ->orderByRaw('(vote_plus - vote_minus) ASC')
                ->orderBy('cms_album_files.views'),
            TopFilter::RecentComments => $this->applyRecentCommentsFilter($query),
            TopFilter::MyComments => $this->applyMyCommentsFilter($query, $currentUserId),
        };
    }

    private function applyRecentCommentsFilter(Builder $query): void
    {
        $since = time() - self::RECENT_COMMENTS_PERIOD;

        $latestComments = Capsule::table('cms_album_comments')
            ->select('sub_id')
            ->selectRaw('MAX(time) AS mtime')
            ->where('time', '>', $since)
            ->groupBy('sub_id');

        $query
            ->join('cms_album_comments as comm', 'cms_album_files.id', '=', 'comm.sub_id')
            ->joinSub($latestComments, 'comm2', static function (JoinClause $join): void {
                $join->on('comm.sub_id', '=', 'comm2.sub_id')
                    ->on('comm.time', '=', 'comm2.mtime');
            })
            ->orderByDesc('comm2.mtime');
    }

    private function applyMyCommentsFilter(Builder $query, int $currentUserId): void
    {
        $query
            ->where('cms_album_files.user_id', $currentUserId)
            ->where('cms_album_files.unread_comments', 1)
            ->orderByDesc(
                Capsule::table('cms_album_comments')
                    ->selectRaw('MAX(time)')
                    ->whereColumn('cms_album_comments.sub_id', 'cms_album_files.id')
            );
    }

    public function countByUsers(array $userIds, ?int $restrictToVisibleForUser): array
    {
        if ($userIds === []) {
            return [];
        }

        $query = AlbumPhoto::query()
            ->select('user_id')
            ->selectRaw('COUNT(*) AS aggregate')
            ->whereIn('user_id', $userIds);

        if ($restrictToVisibleForUser !== null) {
            $query->where(static function (Builder $builder) use ($restrictToVisibleForUser): void {
                $builder
                    ->whereIn('access', [AlbumAccess::Password->value, AlbumAccess::Public->value])
                    ->orWhere('user_id', $restrictToVisibleForUser);
            });
        }

        return $query->groupBy('user_id')->pluck('aggregate', 'user_id')->all();
    }
}
