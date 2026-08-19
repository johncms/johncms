<?php

declare(strict_types=1);

namespace Johncms\Content\Embed;

/**
 * The providers, asked in turn until one recognises the URL.
 *
 * A URL nobody claims is not an error: the text keeps the element the editor wrote, which is
 * what happens today for every site the CMS has no provider for.
 */
final class EmbedProviderRegistry
{
    /** @var list<EmbedProviderInterface>|null */
    private ?array $sorted = null;

    /**
     * @param iterable<EmbedProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers = [],
    ) {
    }

    public function resolve(string $url): ?EmbeddedMedia
    {
        foreach ($this->all() as $provider) {
            $embed = $provider->embed($url);
            if ($embed !== null) {
                return $embed;
            }
        }

        return null;
    }

    /**
     * @return list<EmbedProviderInterface>
     */
    public function all(): array
    {
        if ($this->sorted !== null) {
            return $this->sorted;
        }

        $providers = iterator_to_array($this->providers, false);
        usort(
            $providers,
            static fn (EmbedProviderInterface $a, EmbedProviderInterface $b): int => $b->priority() <=> $a->priority()
        );

        return $this->sorted = $providers;
    }
}
