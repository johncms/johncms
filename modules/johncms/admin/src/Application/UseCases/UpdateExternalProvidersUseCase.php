<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\External\ExternalIdentityProviderRegistry;
use Johncms\Auth\External\ProviderSettings;
use Johncms\Modules\Admin\Domain\Repository\AuthConfigRepositoryInterface;

/**
 * Saves the application keys of the sign-in services into auth.local.php.
 *
 * Whoever installs a module adding a provider should not have to open a config file by hand, and
 * the secrets have to land in the gitignored half of the configuration — which is why this writes
 * `auth.local.php` and never `auth.global.php`.
 */
final readonly class UpdateExternalProvidersUseCase
{
    public function __construct(
        private ExternalIdentityProviderRegistry $registry,
        private AuthConfigRepositoryInterface $config,
    ) {
    }

    /**
     * @param array<string, array{client_id?: string, client_secret?: string, enabled?: bool}> $input
     *                    Keyed by provider. A key nothing is registered for is ignored: the form
     *                    comes from a browser, and the shape of the config is not up to it.
     */
    public function execute(array $input): void
    {
        $providers = [];

        foreach ($input as $key => $values) {
            if (! $this->registry->has($key)) {
                continue;
            }

            $current = ProviderSettings::forProvider($key);
            $secret = trim((string) ($values['client_secret'] ?? ''));

            $providers[$key] = [
                'client_id' => trim((string) ($values['client_id'] ?? '')),
                // An empty field means "leave it as it is": the screen never shows the stored
                // secret, so an empty input is the normal state of a saved provider rather than a
                // request to clear it.
                'client_secret' => $secret === '' ? $current->clientSecret : $secret,
                'enabled'       => (bool) ($values['enabled'] ?? false),
            ];
        }

        if ($providers === []) {
            return;
        }

        $this->config->save(['external' => ['providers' => $providers]]);
    }
}
