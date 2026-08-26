<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Runtime;

use Johncms\View\Menu\MenuArea;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuRegistry;
use Twig\Extension\RuntimeExtensionInterface;

final readonly class MenuRuntime implements RuntimeExtensionInterface
{
    public function __construct(private MenuRegistry $menu)
    {
    }

    /**
     * The items the modules add to a menu — "main" or "admin".
     *
     * Only the ones the visitor may open: what a template gets is already filtered, so a theme
     * never has to know which permission stands behind a line.
     *
     * @return list<MenuItem>
     */
    public function items(string $area = 'main'): array
    {
        return $this->menu->items(MenuArea::tryFrom($area) ?? MenuArea::Main);
    }
}
