<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Auth\External\ExternalIdentityProviderRegistry;
use Johncms\Auth\External\UserIdentity;
use Johncms\Auth\External\UserIdentityRepositoryInterface;
use Johncms\Modules\Profile\Application\DTO\LinkedAccountDTO;

/**
 * The external services the visitor's account is linked to, and the ones they could still link.
 */
final readonly class GetLinkedAccountsUseCase
{
    public function __construct(
        private UserIdentityRepositoryInterface $identities,
        private ExternalIdentityProviderRegistry $registry,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * @return array{linked: list<LinkedAccountDTO>, available: list<array{key: string, label: string}>}
     */
    public function execute(): array
    {
        $userId = $this->currentUser->id();
        $linked = [];
        $linkedKeys = [];

        /** @var UserIdentity $identity */
        foreach ($this->identities->allForUser($userId) as $identity) {
            $provider = $this->registry->get($identity->provider);
            $linkedKeys[$identity->provider] = true;

            $linked[] = new LinkedAccountDTO(
                provider: $identity->provider,
                // A module may have been removed since; the row stays and is shown under its key,
                // so the visitor can still detach it.
                label: $provider?->label() ?? $identity->provider,
                nickname: $identity->nickname ?? '',
                linkedAt: $identity->linked_at,
                available: $provider !== null,
            );
        }

        $available = [];

        foreach ($this->registry->available() as $provider) {
            if (! isset($linkedKeys[$provider->key()])) {
                $available[] = ['key' => $provider->key(), 'label' => $provider->label()];
            }
        }

        return ['linked' => $linked, 'available' => $available];
    }
}
