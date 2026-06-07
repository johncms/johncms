<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Admin\Domain\Enums\BanListSort;

interface BanListRepositoryInterface
{
    /**
     * Список забаненных пользователей (по одной — последней — записи бана на пользователя).
     * К каждой модели добавлены атрибуты `ban_id`, `bantime`, `bancount`.
     */
    public function paginate(BanListSort $sort, int $page, int $perPage): LengthAwarePaginator;
}
