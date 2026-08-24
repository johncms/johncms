<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Infrastructure\Persistence\Repository;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\Modules\Library\Domain\Repository\LibraryTextRepositoryInterface;

class LibraryTextRepository implements LibraryTextRepositoryInterface
{
    public function countTopByField(string $field): int
    {
        return LibraryText::query()->where($field, '>', 0)->count();
    }

    public function countTopByRating(): int
    {
        return (int) Capsule::table('cms_library_rating')->count();
    }

    public function getTopByField(string $field, int $limit): Collection
    {
        return LibraryText::query()
            ->where($field, '>', 0)
            ->orderByDesc($field)
            ->limit($limit)
            ->get();
    }

    public function getTopByRating(int $limit): Collection
    {
        $subquery = Capsule::table('cms_library_rating')
            ->selectRaw('`st_id`, COUNT(*) AS `cnt`, AVG(`point`) AS `avg`')
            ->groupBy('st_id');

        return LibraryText::query()
            ->joinSub($subquery, 'r', 'r.st_id', '=', 'library_texts.id')
            ->orderByDesc('r.avg')
            ->orderByDesc('r.cnt')
            ->limit($limit)
            ->get();
    }

    public function countNew(): int
    {
        return LibraryText::query()
            ->where('time', '>', time() - 259200)
            ->where('premod', 1)
            ->count();
    }

    public function getNew(int $page, int $perPage): Collection
    {
        return LibraryText::query()
            ->where('time', '>', time() - 259200)
            ->where('premod', 1)
            ->orderByDesc('time')
            ->forPage($page, $perPage)
            ->get();
    }

    public function searchCount(string $query, bool $inTitle): int
    {
        $field = $inTitle ? 'name' : 'text';
        return LibraryText::query()
            ->whereRaw('MATCH (`' . $field . '`) AGAINST (? IN BOOLEAN MODE)', [$query])
            ->count();
    }

    public function search(string $query, bool $inTitle, int $page, int $perPage): Collection
    {
        $field = $inTitle ? 'name' : 'text';
        return LibraryText::query()
            ->selectRaw('*, MATCH (`' . $field . '`) AGAINST (? IN BOOLEAN MODE) AS `rel`', [$query])
            ->whereRaw('MATCH (`' . $field . '`) AGAINST (? IN BOOLEAN MODE)', [$query])
            ->orderByDesc('rel')
            ->forPage($page, $perPage)
            ->get();
    }
}
