<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\Exceptions\PasswordRecoveryException;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class GetRecoveryContextUseCase
{
    private const CODE_TTL = 3600;

    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
    ) {
    }

    public function execute(int $id, string $code): User
    {
        if (! $id || strlen($code) !== 32) {
            throw new PasswordRecoveryException(__('Wrong data'));
        }

        $user = $this->profileUserRepository->findById($id);
        if ($user === null) {
            throw new PasswordRecoveryException(__('User does not exists'));
        }

        if (empty($user->rest_code) || empty($user->rest_time)) {
            throw new PasswordRecoveryException(__('Password recovery is impossible'));
        }

        if ($user->rest_time < time() - self::CODE_TTL || $code !== $user->rest_code) {
            // Invalidate the request so an expired/incorrect link cannot be retried
            $this->profileUserRepository->clearPasswordRecovery($user->id);
            throw new PasswordRecoveryException(__('Time allotted for the password recovery has been exceeded'));
        }

        return $user;
    }
}
