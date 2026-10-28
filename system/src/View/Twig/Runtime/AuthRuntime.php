<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Runtime;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Auth\External\ExternalIdentityProviderInterface;
use Johncms\Auth\External\ExternalIdentityProviderRegistry;
use Johncms\Auth\Impersonation\ImpersonationBannerDTO;
use Johncms\Users\ProfileLinksInterface;
use Twig\Extension\RuntimeExtensionInterface;

final readonly class AuthRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
        private ExternalIdentityProviderRegistry $providers,
        private ProfileLinksInterface $profileLinks,
    ) {
    }

    public function can(string $permission, mixed $subject = null): bool
    {
        return $this->accessChecker->allows($permission, $subject);
    }

    /**
     * Where the name of an author leads, or null to draw the name without a link. An author
     * without an account comes as null.
     */
    public function profileUrl(?int $userId): ?string
    {
        return $userId === null ? null : $this->profileLinks->urlOf($userId);
    }

    /**
     * The services this site can be signed in with, for the buttons on both login screens.
     * Empty when none is switched on, so a site that uses none draws no empty block.
     *
     * @return list<array{key: string, label: string, icon: string}>
     */
    public function externalProviders(): array
    {
        return array_values(
            array_map(
                static fn (ExternalIdentityProviderInterface $provider): array => [
                    'key'   => $provider->key(),
                    'label' => $provider->label(),
                    'icon'  => $provider->icon(),
                ],
                $this->providers->available()
            )
        );
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
