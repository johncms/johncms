<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Downloads\Application\Exceptions\DownloadsAccessDeniedException;
use Johncms\Modules\Downloads\Application\Exceptions\DownloadsErrorCode;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;

final readonly class EnsureDownloadsAccessUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * @throws DownloadsAccessDeniedException
     */
    public function execute(): void
    {
        if ($this->accessChecker->allows(DownloadsPermissions::VIEW)) {
            return;
        }

        // A guest is told to sign in and everybody else that the section is closed; which of the
        // two it is depends on the user role, and only the first is actionable.
        throw new DownloadsAccessDeniedException(
            $this->currentUser->isGuest()
                ? DownloadsErrorCode::DOWNLOADS_AUTH_REQUIRED
                : DownloadsErrorCode::DOWNLOADS_CLOSED
        );
    }
}
