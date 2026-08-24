<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\System\Users\UserClean;

final readonly class DeleteUserUseCase
{
    public function __construct(
        private UserClean $userClean,
    ) {
    }

    /**
     * Каскадно удаляет пользователя. Комментарии и контент форума удаляются
     * (точнее, скрываются) только если запрошено в форме.
     */
    public function execute(int $id, bool $deleteComments, bool $deleteForum): void
    {
        $this->userClean->removeAlbum($id);
        $this->userClean->removeGuestbook($id);
        $this->userClean->removeMail($id);
        $this->userClean->removeKarma($id);

        if ($deleteComments) {
            $this->userClean->cleanComments($id);
        }

        if ($deleteForum) {
            $this->userClean->cleanForum($id);
        }

        $this->userClean->removeUser($id);
    }
}
