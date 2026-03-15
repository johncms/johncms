<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Users\User;

final readonly class EnsureCuratorsAccessUseCase
{
    public function __construct(
        private User $currentUser,
    ) {
    }

    public function execute(): void
    {
        if ($this->currentUser->rights < 7) {
            throw new AccessDeniedException('Access denied to manage curators.');
        }
    }
}
