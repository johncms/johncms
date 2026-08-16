<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\External;

/**
 * The providers this installation knows about: the four shipped ones plus whatever the installed
 * modules registered with the `johncms.auth.external_provider` tag.
 *
 * "Known" and "offered" are separate questions. A provider of a switched-off module is not in
 * here at all, one whose keys are missing is here but not offered, and an account linked to a
 * provider that has since disappeared still has to be listed in the profile — which is why
 * lookups by key answer null instead of failing.
 */
final class ExternalIdentityProviderRegistry
{
    /** @var array<string, ExternalIdentityProviderInterface> */
    private array $providers = [];

    /**
     * @param iterable<ExternalIdentityProviderInterface> $providers
     */
    public function __construct(iterable $providers = [])
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->key()] = $provider;
        }
    }

    public function get(string $key): ?ExternalIdentityProviderInterface
    {
        return $this->providers[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->providers[$key]);
    }

    /**
     * Everything registered, configured or not. What the panel lists.
     *
     * @return array<string, ExternalIdentityProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }

    /**
     * The providers a visitor may actually sign in with: switched on and holding their keys.
     *
     * @return array<string, ExternalIdentityProviderInterface>
     */
    public function available(): array
    {
        return array_filter(
            $this->providers,
            static fn (ExternalIdentityProviderInterface $provider): bool => $provider->isConfigured()
        );
    }
}
