<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Utils\DateFormatterInterface;

/**
 * What the main page of the downloads and a category show alike: the cards of the subcategories
 * and the files of one level, sorted as the query asks.
 */
final readonly class CatalogPresenter
{
    public const array SORT_FIELDS = ['time', 'name'];
    public const array SORT_ORDERS = ['desc', 'asc'];

    public function __construct(
        private FilePresenter $filePresenter,
        private DownloadCategoryPathService $categoryPathService,
        private DateFormatterInterface $dateFormatter,
    ) {
    }

    /**
     * The subcategories of a category (0 for the main page), each with the date of the newest
     * file anywhere inside it.
     *
     * @return list<array<string, mixed>>
     */
    public function categories(int $parentId): array
    {
        /** @var Collection<int, DownloadCategory> $categories */
        $categories = DownloadCategory::query()->where('refid', $parentId)->orderBy('sort')->get();

        if ($categories->isEmpty()) {
            return [];
        }

        // One query for every card: the newest file of each folder, summed up by prefix below.
        $lastByDir = DownloadFile::query()
            ->where('type', 2)
            ->groupBy('dir')
            ->selectRaw('dir, MAX(time) AS last_time')
            ->pluck('last_time', 'dir')
            ->all();

        // A category with subcategories cannot be deleted; one query tells which ones have them.
        $withChildren = DownloadCategory::query()
            ->whereIn('refid', $categories->pluck('id')->all())
            ->distinct()
            ->pluck('refid')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $cards = [];
        $last = count($categories) - 1;

        foreach ($categories->values() as $position => $category) {
            $lastTime = $this->lastTime((string) $category->dir, $lastByDir);
            $cards[] = [
                'id'          => $category->id,
                'rus_name'    => $category->rus_name,
                'desc'        => $category->desc,
                'total'       => (int) $category->total,
                'url'         => $this->categoryPathService->getCategoryUrl($category),
                'last_date'   => $lastTime > 0 ? $this->dateFormatter->day($lastTime) : '',
                'sort_url'    => '/downloads/categories/' . $category->id . '/sort',
                'edit_url'    => '/downloads/categories/' . $category->id . '/edit',
                'delete_url'  => '/downloads/categories/' . $category->id . '/delete',
                'is_first'    => $position === 0,
                'is_last'     => $position === $last,
                'has_children' => in_array($category->id, $withChildren, true),
            ];
        }

        return $cards;
    }

    /**
     * The sorting the visitor chose, from the query (?sort=…&order=…): the address keeps it, so a
     * link, a reload and the back button show the same order. Anything unknown is the default.
     *
     * @return array{field: string, order: string}
     */
    public function sort(string $field, string $order): array
    {
        return [
            'field' => in_array($field, self::SORT_FIELDS, true) ? $field : 'time',
            'order' => in_array($order, self::SORT_ORDERS, true) ? $order : 'desc',
        ];
    }

    public function countFiles(int $categoryId): int
    {
        return DownloadFile::query()->where('refid', $categoryId)->where('type', 2)->count();
    }

    /**
     * One page of the files of a category (0 for the main page).
     *
     * @param array{field: string, order: string} $sort
     * @return list<array<string, mixed>>
     */
    public function files(int $categoryId, array $sort, int $offset, int $limit): array
    {
        /** @var Collection<int, DownloadFile> $rows */
        $rows = DownloadFile::query()
            ->where('refid', $categoryId)
            ->where('type', 2)
            ->orderBy($sort['field'] === 'name' ? 'rus_name' : 'time', $sort['order'])
            ->orderBy('id', $sort['order'])
            ->offset($offset)
            ->limit($limit)
            ->get();

        $files = [];
        foreach ($rows as $file) {
            $files[] = $this->filePresenter->present($file);
        }

        return $files;
    }

    /**
     * Links of the sorting control: each keeps the other half of the choice and starts from the
     * first page.
     *
     * @param array{field: string, order: string} $sort
     * @return array{fields: list<array{label: string, url: string, active: bool}>, order_url: string, ascending: bool}
     */
    public function sortLinks(string $baseUrl, array $sort): array
    {
        $labels = ['time' => __('by time'), 'name' => __('by name')];
        $fields = [];

        foreach (self::SORT_FIELDS as $field) {
            $fields[] = [
                'label'  => $labels[$field],
                'url'    => $this->sortUrl($baseUrl, $field, $sort['order']),
                'active' => $field === $sort['field'],
            ];
        }

        return [
            'fields'    => $fields,
            'order_url' => $this->sortUrl($baseUrl, $sort['field'], $sort['order'] === 'desc' ? 'asc' : 'desc'),
            'ascending' => $sort['order'] === 'asc',
        ];
    }

    private function sortUrl(string $baseUrl, string $field, string $order): string
    {
        $query = [];

        if ($field !== 'time') {
            $query['sort'] = $field;
        }

        if ($order !== 'desc') {
            $query['order'] = $order;
        }

        return $baseUrl . ($query ? '?' . http_build_query($query) : '') . '#downloads-files';
    }

    /** @param array<string, int|string> $lastByDir */
    private function lastTime(string $dir, array $lastByDir): int
    {
        $last = 0;

        foreach ($lastByDir as $fileDir => $time) {
            $fileDir = (string) $fileDir;
            if ($fileDir === $dir || str_starts_with($fileDir, $dir . '/')) {
                $last = max($last, (int) $time);
            }
        }

        return $last;
    }
}
