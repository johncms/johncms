<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\External;

use Johncms\Auth\External\ExternalAuthContextDTO;
use Johncms\Auth\External\ExternalCallbackDTO;
use Johncms\Auth\External\ExternalIdentityDTO;
use Johncms\Auth\External\ExternalIdentityProviderInterface;
use Johncms\Auth\External\ExternalIdentityProviderRegistry;
use PHPUnit\Framework\TestCase;

/**
 * The registry a module joins by tagging a provider.
 *
 * The requirement behind these tests: somebody must be able to add a service with a module and to
 * remove it again, and neither must break the site — a linked account of a provider that is gone
 * still has to be listed so it can be detached.
 */
final class ExternalIdentityProviderRegistryTest extends TestCase
{
    public function testAProviderFromAModuleIsRegisteredByItsKey(): void
    {
        $registry = new ExternalIdentityProviderRegistry([$this->provider('telegram')]);

        self::assertTrue($registry->has('telegram'));
        self::assertSame('telegram', $registry->get('telegram')?->key());
    }

    /**
     * A row of `user_identities` may name a provider whose module has been removed, and reading
     * it must not be an error.
     */
    public function testAnUnknownKeyAnswersNullInsteadOfFailing(): void
    {
        $registry = new ExternalIdentityProviderRegistry([]);

        self::assertNull($registry->get('telegram'));
        self::assertFalse($registry->has('telegram'));
    }

    /**
     * Registered and offered are separate questions: a provider whose keys the site never filled
     * in is listed in the panel and shown to nobody else.
     */
    public function testOnlyConfiguredProvidersAreOffered(): void
    {
        $registry = new ExternalIdentityProviderRegistry([
            $this->provider('github', configured: true),
            $this->provider('vk', configured: false),
        ]);

        self::assertSame(['github', 'vk'], array_keys($registry->all()));
        self::assertSame(['github'], array_keys($registry->available()));
    }

    private function provider(string $key, bool $configured = true): ExternalIdentityProviderInterface
    {
        return new class ($key, $configured) implements ExternalIdentityProviderInterface {
            public function __construct(private readonly string $providerKey, private readonly bool $configured)
            {
            }

            public function key(): string
            {
                return $this->providerKey;
            }

            public function label(): string
            {
                return ucfirst($this->providerKey);
            }

            public function icon(): string
            {
                return $this->providerKey;
            }

            public function isConfigured(): bool
            {
                return $this->configured;
            }

            public function startUrl(ExternalAuthContextDTO $context): string
            {
                return 'https://example.com/authorize?state=' . $context->state;
            }

            public function handleCallback(
                ExternalCallbackDTO $callback,
                ExternalAuthContextDTO $context
            ): ExternalIdentityDTO {
                return new ExternalIdentityDTO($callback->get('code'));
            }
        };
    }
}
