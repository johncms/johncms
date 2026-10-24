<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Repository\RegistrationModerationRepositoryInterface;

/**
 * Confirms the e-mail of an account from the panel, for an owner who cannot reach the link the
 * site mailed: the account signs in from then on, and the cleanup no longer removes it.
 */
final readonly class ConfirmUserEmailUseCase
{
    public function __construct(
        private RegistrationModerationRepositoryInterface $repository,
    ) {
    }

    public function execute(int $id): void
    {
        $this->repository->confirmEmail($id);
    }
}
