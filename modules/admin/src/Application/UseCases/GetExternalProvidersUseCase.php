<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\External\ExternalIdentityProviderInterface;
use Johncms\Auth\External\ExternalIdentityProviderRegistry;
use Johncms\Auth\External\ProviderSettings;
use Johncms\Auth\External\UserIdentityRepositoryInterface;
use Johncms\Modules\Admin\Application\DTO\ExternalProviderRowDTO;

/**
 * The sign-in services as the panel lists them: what is registered, what is configured, and how
 * many people would be affected by switching one off.
 */
final readonly class GetExternalProvidersUseCase
{
    public function __construct(
        private ExternalIdentityProviderRegistry $registry,
        private UserIdentityRepositoryInterface $identities,
    ) {
    }

    /**
     * @return list<ExternalProviderRowDTO>
     */
    public function execute(string $homeUrl): array
    {
        return array_values(
            array_map(
                function (ExternalIdentityProviderInterface $provider) use ($homeUrl): ExternalProviderRowDTO {
                    $settings = ProviderSettings::forProvider($provider->key());
                    $usage = $this->identities->usageOf($provider->key());

                    return new ExternalProviderRowDTO(
                        key: $provider->key(),
                        label: $provider->label(),
                        enabled: $settings->enabled,
                        clientId: $settings->clientId,
                        // The secret itself is never sent back to the browser: an input holding it
                        // would put it in every page cache and every screenshot of this screen.
                        hasSecret: $settings->clientSecret !== '',
                        callbackUrl: rtrim($homeUrl, '/') . '/auth/' . $provider->key() . '/callback',
                        users: $usage['users'],
                        withoutAlternative: $usage['without_alternative'],
                    );
                },
                $this->registry->all()
            )
        );
    }
}
