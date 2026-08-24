<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;

final class ConfirmNewEmailUseCase
{
    public function __construct(
        private readonly ProfileUserRepositoryInterface $repository,
    ) {
    }

    public function run(int $id, string $code): bool
    {
        $user = $this->repository->findById($id);
        if ($user === null || $code === '' || empty($user->new_email) || $user->confirmation_code !== $code) {
            return false;
        }

        $this->repository->confirmNewEmail($id, $user->new_email);

        return true;
    }
}
