<?php

declare(strict_types=1);

namespace Tests\Unit\Content\Embed;

use Johncms\Content\Embed\EmbeddedMedia;
use Johncms\Content\Embed\EmbedProviderInterface;
use Johncms\Content\Embed\EmbedProviderRegistry;
use PHPUnit\Framework\TestCase;

final class EmbedProviderRegistryTest extends TestCase
{
    public function testTheFirstProviderThatRecognisesTheAddressAnswers(): void
    {
        $registry = new EmbedProviderRegistry([
            $this->provider(priority: 0, template: 'low', claims: false),
            $this->provider(priority: 0, template: 'answering', claims: true),
        ]);

        self::assertSame('answering', $registry->resolve('https://example.org/x')?->template);
    }

    /**
     * This is how a module replaces a built-in provider: it claims the same addresses and asks
     * to be tried first, without the core knowing anything about it.
     */
    public function testAProviderOfAHigherPriorityIsAskedFirst(): void
    {
        $registry = new EmbedProviderRegistry([
            $this->provider(priority: 0, template: 'built-in', claims: true),
            $this->provider(priority: 100, template: 'of the module', claims: true),
        ]);

        self::assertSame('of the module', $registry->resolve('https://example.org/x')?->template);
    }

    public function testAnAddressNobodyClaimsHasNoPlayer(): void
    {
        $registry = new EmbedProviderRegistry([$this->provider(priority: 0, template: 'x', claims: false)]);

        self::assertNull($registry->resolve('https://example.org/x'));
    }

    public function testAnInstallationWithoutProvidersAnswersWithNothing(): void
    {
        self::assertNull((new EmbedProviderRegistry())->resolve('https://example.org/x'));
    }

    private function provider(int $priority, string $template, bool $claims): EmbedProviderInterface
    {
        return new class ($priority, $template, $claims) implements EmbedProviderInterface {
            public function __construct(
                private readonly int $priority,
                private readonly string $template,
                private readonly bool $claims,
            ) {
            }

            public function priority(): int
            {
                return $this->priority;
            }

            public function embed(string $url): ?EmbeddedMedia
            {
                return $this->claims ? new EmbeddedMedia($this->template) : null;
            }
        };
    }
}
