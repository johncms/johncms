<?php

declare(strict_types=1);

namespace Johncms\View\Menu;

use Illuminate\Support\Arr;

class Menu
{
    public const ADMIN_SIDEBAR = 'admin_sidebar';
    public const ADMIN_USER = 'admin_user';
    public const PUBLIC_SIDEBAR = 'public_sidebar';
    public const PUBLIC_USER = 'public_user';
    public const PUBLIC_ADMIN = 'public_admin';

    /** @var MenuItemInterface[] */
    protected array $items = [];

    /** @var array{parent: string, item: MenuItemInterface} */
    protected array $children = [];

    public function add(MenuItemInterface $menuItem): static
    {
        $this->items[$menuItem->getCode()] = $menuItem;
        return $this;
    }

    public function addChildren(string $parentCode, MenuItemInterface $menuItem): static
    {
        $this->children[] = ['parent' => $parentCode, 'item' => $menuItem];
        return $this;
    }

    public function getItems(): array
    {
        $items = [];
        foreach ($this->items as $key => $item) {
            $items[$key] = $item->toArray();
        }

        // Insert children menu items
        $allChildItems = [];
        foreach ($this->children as $item) {
            $key = str_replace('.', '.children.', $item['parent']);
            $key .= '.children.' . $item['item']->getCode();
            $childItems = [];
            Arr::set($childItems, $key, $item['item']->toArray());
            $allChildItems = array_replace_recursive($allChildItems, $childItems);
        }

        return array_replace_recursive($items, $allChildItems);
    }
}
