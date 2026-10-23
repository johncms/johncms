<?php

declare(strict_types=1);

namespace Johncms\View\Homepage;

/**
 * Everything the switched-on modules put on the home page (HomepageBlockProviderInterface).
 */
final readonly class HomepageBlocks
{
    /**
     * @param iterable<HomepageBlockProviderInterface> $providers
     */
    public function __construct(private iterable $providers)
    {
    }

    /**
     * @return list<HomepageBlock> Lighter first; equal weights keep the order of the modules.
     */
    public function all(): array
    {
        $blocks = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->homepageBlocks() as $block) {
                $blocks[] = $block;
            }
        }

        usort($blocks, static fn (HomepageBlock $left, HomepageBlock $right): int => $left->weight <=> $right->weight);

        return $blocks;
    }
}
