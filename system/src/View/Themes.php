<?php

declare(strict_types=1);

namespace Johncms\View;

class Themes
{
    public function getThemes(): array
    {
        $themes = array_map('basename', glob(ROOT_PATH . 'themes/*', GLOB_ONLYDIR));
        $adminTheme = array_search('admin', $themes);
        if ($adminTheme !== false) {
            unset($themes[$adminTheme]);
        }
        return $themes;
    }
}
