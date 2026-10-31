<?php

declare(strict_types=1);

namespace Johncms\View\Dashboard;

/**
 * Everything the switched-on modules ask the administrator to look at
 * (DashboardAttentionProviderInterface).
 */
final readonly class DashboardAttention
{
    /**
     * @param iterable<DashboardAttentionProviderInterface> $providers
     */
    public function __construct(private iterable $providers)
    {
    }

    /**
     * @return list<AttentionItem> The most pressing first; equal levels keep the order of the modules.
     */
    public function all(): array
    {
        $items = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->attentionItems() as $item) {
                $items[] = $item;
            }
        }

        $order = array_flip(array_map(static fn (AttentionLevel $level): string => $level->value, AttentionLevel::cases()));
        usort($items, static fn (AttentionItem $left, AttentionItem $right): int => $order[$left->level->value] <=> $order[$right->level->value]);

        return $items;
    }
}
