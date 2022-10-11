<?php

declare(strict_types=1);

namespace Johncms\View\Menu;

class Menu
{
    /** @var MenuItem[] */
    protected array $items = [];

    public function add(MenuItem $menuItem): static
    {
        $this->items[] = $menuItem;
        return $this;
    }

    public function getItems(): array
    {
        $items = [];
        foreach ($this->items as $item) {
            $items[] = $item->toArray();
        }
        return $items;
    }
}
