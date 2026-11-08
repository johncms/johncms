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
    /**
     * Everything the providers gave, by area, before access is checked. The providers are asked
     * once per request: a page draws several menus, and a provider may read the database to
     * decide what it offers.
     *
     * @var array<string, list<MenuItem>>|null
     */
    private ?array $offered = null;

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

        $items = array_values(array_filter(
            $this->offered()[$area->value] ?? [],
            fn (MenuItem $item): bool => $item->permission === null || $this->access->allows($item->permission)
        ));

        usort(
            $items,
            static fn (MenuItem $left, MenuItem $right): int
                => [$left->weight, $left->title] <=> [$right->weight, $right->title]
        );

        return $this->items[$area->value] = $items;
    }

    public function reset(): void
    {
        $this->offered = null;
        $this->items = [];
    }

    /**
     * @return array<string, list<MenuItem>>
     */
    private function offered(): array
    {
        if ($this->offered !== null) {
            return $this->offered;
        }

        $offered = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->menuItems() as $item) {
                $offered[$item->area->value][] = $item;
            }
        }

        return $this->offered = $offered;
    }
}
