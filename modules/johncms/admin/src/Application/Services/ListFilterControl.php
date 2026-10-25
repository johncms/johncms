<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

/**
 * One dropdown filter of a list of the panel, as components/ui/filter-dropdown.twig draws it, and
 * the chip it leaves among the applied filters (components/ui/filter-chips.twig).
 *
 * The first option is "any": choosing it is no filter at all, so it leaves no chip.
 */
final readonly class ListFilterControl
{
    /**
     * @param list<array<string, mixed>> $options  title, url, active.
     * @param string                     $clearUrl The list without this filter.
     * @return array<string, mixed>
     */
    public function build(string $name, string $label, array $options, string $clearUrl): array
    {
        $active = array_values(array_filter($options, static fn (array $option): bool => $option['active']));
        $current = $active[0] ?? $options[0];
        $applied = $current !== $options[0];

        return [
            'name'    => $name,
            'label'   => $label,
            'value'   => $current['title'],
            'applied' => $applied,
            'options' => $options,
            'chip'    => $applied ? ['label' => $label, 'value' => $current['title'], 'url' => $clearUrl] : null,
        ];
    }
}
