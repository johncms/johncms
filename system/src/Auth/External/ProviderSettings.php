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
 * The keys of one external service, as the site configured them.
 *
 * Read from config at call time rather than injected: the panel writes them into
 * `auth.local.php`, and a value baked into the compiled container would keep being the old one.
 */
final readonly class ProviderSettings
{
    public function __construct(
        public string $clientId = '',
        public string $clientSecret = '',
        public bool $enabled = false,
    ) {
    }

    public static function forProvider(string $key): self
    {
        /** @var array<string, mixed> $values */
        $values = (array) config('auth.external.providers.' . $key, []);

        return new self(
            clientId: (string) ($values['client_id'] ?? ''),
            clientSecret: (string) ($values['client_secret'] ?? ''),
            // Absent means off: a provider starts working when somebody turns it on, never
            // because its class happened to be installed.
            enabled: (bool) ($values['enabled'] ?? false),
        );
    }

    /**
     * Whether the provider has what it needs to talk to its service at all.
     */
    public function areComplete(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }
}
