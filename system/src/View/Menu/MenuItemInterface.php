<?php

declare(strict_types=1);

namespace Johncms\View\Menu;

interface MenuItemInterface
{
    /**
     * This method should return the unique menu item code.
     * This is used to add child menu items.
     *
     * @return string
     */
    public function getCode(): string;

    public function toArray(): array;
}
