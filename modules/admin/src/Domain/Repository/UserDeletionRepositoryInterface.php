<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Johncms\Users\User;

interface UserDeletionRepositoryInterface
{
    public function findById(int $id): ?User;

    /**
     * Суммарное число комментариев пользователя (библиотека, загрузки,
     * личные гостевые, альбомы).
     */
    public function countComments(int $id): int;

    public function countForumTopics(int $id): int;

    public function countForumPosts(int $id): int;
}
