<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Modules\Downloads\Application\Exceptions\DownloadsAccessDeniedException;
use Johncms\Modules\Downloads\Application\Exceptions\DownloadsErrorCode;
use Johncms\Users\User;

final readonly class EnsureDownloadsAccessUseCase
{
    public function __construct(
        private User $currentUser,
    ) {
    }

    /**
     * @throws DownloadsAccessDeniedException
     */
    public function execute(): void
    {
        $config = config('johncms');

        if (! $config['mod_down'] && $this->currentUser->rights < 7) {
            throw new DownloadsAccessDeniedException(DownloadsErrorCode::DOWNLOADS_CLOSED);
        }

        if ($config['mod_down'] === 1 && ! $this->currentUser->isValid()) {
            throw new DownloadsAccessDeniedException(DownloadsErrorCode::DOWNLOADS_AUTH_REQUIRED);
        }
    }
}
