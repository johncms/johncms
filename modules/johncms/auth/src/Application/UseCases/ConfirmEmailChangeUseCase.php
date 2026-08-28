<?php

declare(strict_types=1);

namespace Johncms\Modules\Auth\Application\UseCases;

use Johncms\Modules\Auth\Domain\Repository\AuthUserRepositoryInterface;

final class ConfirmEmailChangeUseCase
{
    public function __construct(
        private readonly AuthUserRepositoryInterface $repository,
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
