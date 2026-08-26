<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\View\Menu;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The menu items the modules add, for the visitor being served.
 *
 * Access is decided here rather than in the template: a module says which permission its page
 * needs, and an item the visitor may not open is not drawn at all. The answer belongs to one
 * request, so it is thrown away between them.
 */
final class MenuRegistry implements ResetInterface
{
    /** @var array<string, list<MenuItem>> */
    private array $items = [];

    /**
     * @param iterable<MenuItemProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly AccessCheckerInterface $access,
    ) {
    }

    /**
     * @return list<MenuItem> Sorted by weight, then by title.
     */
    public function items(MenuArea $area): array
    {
        if (isset($this->items[$area->value])) {
            return $this->items[$area->value];
        }

        $items = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->menuItems() as $item) {
                if ($item->area !== $area) {
                    continue;
                }

                if ($item->permission !== null && ! $this->access->allows($item->permission)) {
                    continue;
                }

                $items[] = $item;
            }
        }

        usort(
            $items,
            static fn (MenuItem $left, MenuItem $right): int
                => [$left->weight, $left->title] <=> [$right->weight, $right->title]
        );

        return $this->items[$area->value] = $items;
    }

    public function reset(): void
    {
        $this->items = [];
    }
}
