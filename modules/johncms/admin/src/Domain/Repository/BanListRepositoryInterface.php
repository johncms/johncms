<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Domain\Enums\BanListSort;
use Johncms\Users\User;

interface BanListRepositoryInterface
{
    /**
     * Количество забаненных пользователей (по одной — последней — записи бана на пользователя).
     */
    public function count(): int;

    /**
     * Страница забаненных пользователей (по одной — последней — записи бана на пользователя).
     * К каждой модели добавлены атрибуты `ban_id`, `bantime`, `bancount`.
     *
     * @return Collection<int, User>
     */
    public function get(BanListSort $sort, int $limit, int $offset): Collection;
}
