<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

interface DashboardRepositoryInterface
{
    /**
     * Количество пользователей, активных с указанного момента (по lastdate).
     */
    public function countActiveUsersSince(int $timestamp): int;

    /**
     * Количество подтверждённых пользователей, зарегистрированных с указанного момента (по datereg).
     */
    public function countRegisteredUsersSince(int $timestamp): int;

    /**
     * Количество сообщений форума, созданных с указанного момента (по date).
     */
    public function countForumMessagesSince(int $timestamp): int;
}
