<?php

declare(strict_types=1);

namespace Johncms\View\Menu;

class MenuFactory
{
    private static array $menu = [];

    public static function create(string $type): Menu
    {
        if (array_key_exists($type, self::$menu)) {
            return self::$menu[$type];
        }

        self::$menu[$type] = new Menu();
        return self::$menu[$type];
    }
}
