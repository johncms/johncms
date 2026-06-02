<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\ChangePasswordCommand;
use Johncms\Modules\Profile\Application\Exceptions\ChangePasswordException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class ChangePasswordUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private User $currentUser,
    ) {
    }

    public function execute(ChangePasswordCommand $command): void
    {
        $profileUser = $this->profileUserRepository->findById($command->profileUserId);
        if ($profileUser === null) {
            throw new ProfileNotFoundException();
        }

        $isSelf = $profileUser->id === $this->currentUser->id;

        $errors = [];
        if ($isSelf) {
            if (! $command->oldPassword || ! $command->newPassword || ! $command->confirmPassword) {
                $errors[] = __('It is necessary to fill in all fields');
            }
        } elseif (! $command->newPassword || ! $command->confirmPassword) {
            $errors[] = __('It is necessary to fill in all fields');
        }

        if (! $errors && $isSelf && md5(md5($command->oldPassword)) !== $profileUser->password) {
            $errors[] = __('Old password entered incorrectly');
        }

        if ($command->newPassword !== $command->confirmPassword) {
            $errors[] = __('The password confirmation you entered is wrong');
        }

        if (! $errors && strlen($command->newPassword) < 3) {
            $errors[] = __('The password must contain at least 3 characters');
        }

        if ($errors) {
            throw new ChangePasswordException($errors);
        }

        $this->profileUserRepository->updatePassword($profileUser->id, md5(md5($command->newPassword)));
    }
}
