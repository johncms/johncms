<?php

declare(strict_types=1);

namespace Johncms\Modules\Auth\Application\UseCases;

use Johncms\Auth\Password\PasswordResetTokens;
use Johncms\Modules\Auth\Application\Exceptions\PasswordRecoveryException;
use Johncms\Modules\Auth\Domain\Repository\AuthUserRepositoryInterface;
use Johncms\Users\User;

final readonly class GetRecoveryContextUseCase
{
    public function __construct(
        private AuthUserRepositoryInterface $users,
        private PasswordResetTokens $resetTokens,
    ) {
    }

    /**
     * Checks the recovery link without spending it: the form behind it may be opened and
     * abandoned, and only submitting it consumes the token.
     */
    public function execute(int $id, string $code): User
    {
        // The identifier in the link is a convenience, not the credential: the token decides
        // whose account this is, and a mismatch means the link was tampered with.
        if ($id < 1 || $this->resetTokens->verify($code) !== $id) {
            throw new PasswordRecoveryException(__('Time allotted for the password recovery has been exceeded'));
        }

        $user = $this->users->findById($id);

        if ($user === null) {
            throw new PasswordRecoveryException(__('User does not exists'));
        }

        return $user;
    }
}
