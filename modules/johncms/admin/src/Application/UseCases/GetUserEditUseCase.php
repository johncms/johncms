<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\Access\UserEditAccess;
use Johncms\Modules\Admin\Application\Exceptions\UserEditForbiddenException;
use Johncms\Modules\Admin\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Admin\Domain\Repository\UserEditRepositoryInterface;
use Johncms\Users\User;

/**
 * The account a form of the panel changes, once it is known the visitor may change it.
 */
final readonly class GetUserEditUseCase
{
    public function __construct(
        private UserEditRepositoryInterface $users,
        private UserEditAccess $access,
    ) {
    }

    public function forProfile(int $id): User
    {
        $user = $this->find($id);

        return $this->access->mayEdit($user) ? $user : throw new UserEditForbiddenException();
    }

    public function forPassword(int $id): User
    {
        $user = $this->find($id);

        return $this->access->mayChangePassword($user) ? $user : throw new UserEditForbiddenException();
    }

    public function forSettingsReset(int $id): User
    {
        $user = $this->find($id);

        return $this->access->mayResetSettings($user) ? $user : throw new UserEditForbiddenException();
    }

    private function find(int $id): User
    {
        return $this->users->findById($id) ?? throw new UserNotFoundException();
    }
}
