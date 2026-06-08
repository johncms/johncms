<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

interface InactiveUsersRepositoryInterface
{
    /**
     * Количество «мёртвых» профилей (старая регистрация, давний последний визит,
     * нулевая активность на форуме/в гостевой/в комментариях).
     */
    public function countInactive(): int;

    /**
     * @return list<int> Идентификаторы «мёртвых» профилей.
     */
    public function getInactiveIds(): array;

    /**
     * Удаляет отметки прочтения форума (cms_forum_rdm) для указанных пользователей.
     *
     * @param list<int> $userIds
     */
    public function deleteForumReadMarks(array $userIds): void;
}
