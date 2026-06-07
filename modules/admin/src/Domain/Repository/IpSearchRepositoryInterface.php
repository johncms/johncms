<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IpSearchRepositoryInterface
{
    /**
     * Пользователи, чей текущий IP (или IP за прокси) попадает в диапазон.
     */
    public function paginateUsers(int $from, int $to, int $page, int $perPage): LengthAwarePaginator;

    /**
     * Пользователи, чья последняя запись в истории IP попадает в диапазон.
     * Отображаемый IP берётся из исторической записи.
     */
    public function paginateHistory(int $from, int $to, int $page, int $perPage): LengthAwarePaginator;
}
