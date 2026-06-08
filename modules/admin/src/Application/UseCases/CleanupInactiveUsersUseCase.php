<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Repository\InactiveUsersRepositoryInterface;
use Johncms\System\Users\UserClean;

final readonly class CleanupInactiveUsersUseCase
{
    public function __construct(
        private InactiveUsersRepositoryInterface $repository,
        private UserClean $userClean,
    ) {
    }

    /**
     * Каскадно удаляет «мёртвые» профили и связанные данные. Возвращает число удалённых.
     */
    public function execute(): int
    {
        $ids = $this->repository->getInactiveIds();
        if ($ids === []) {
            return 0;
        }

        foreach ($ids as $id) {
            $this->userClean->removeAlbum($id);
            $this->userClean->removeGuestbook($id);
            $this->userClean->removeMail($id);
            $this->userClean->removeKarma($id);
            $this->userClean->cleanComments($id);
            $this->userClean->removeUser($id);
        }

        $this->repository->deleteForumReadMarks($ids);

        return count($ids);
    }
}
