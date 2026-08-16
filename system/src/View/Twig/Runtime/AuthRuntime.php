<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Runtime;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Auth\Impersonation\ImpersonationBannerDTO;
use Twig\Extension\RuntimeExtensionInterface;

final readonly class AuthRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
    ) {
    }

    public function can(string $permission, mixed $subject = null): bool
    {
        return $this->accessChecker->allows($permission, $subject);
    }

    /**
     * The banner every page carries while an administrator browses as somebody else, or null the
     * rest of the time. Browsing as a user is invisible to the user, never to the administrator
     * doing it — one that forgets they are somebody else is how posts get written by accident.
     */
    public function impersonation(): ?ImpersonationBannerDTO
    {
        $identity = $this->currentUser->identity();

        if (! $identity->isImpersonating()) {
            return null;
        }

        return new ImpersonationBannerDTO($identity->userId, (string) $this->currentUser->user()->name);
    }
}
