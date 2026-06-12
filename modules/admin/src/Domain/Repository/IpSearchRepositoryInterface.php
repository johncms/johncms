<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Users\User;

interface IpSearchRepositoryInterface
{
    /**
     * Количество пользователей, чей текущий IP (или IP за прокси) попадает в диапазон.
     */
    public function countUsers(int $from, int $to): int;

    /**
     * Пользователи, чей текущий IP (или IP за прокси) попадает в диапазон.
     *
     * @return Collection<int, User>
     */
    public function getUsers(int $from, int $to, int $limit, int $offset): Collection;

    /**
     * Количество пользователей, чья последняя запись в истории IP попадает в диапазон.
     */
    public function countHistory(int $from, int $to): int;

    /**
     * Пользователи, чья последняя запись в истории IP попадает в диапазон.
     * Отображаемый IP берётся из исторической записи.
     *
     * @return Collection<int, User>
     */
    public function getHistory(int $from, int $to, int $limit, int $offset): Collection;
}
