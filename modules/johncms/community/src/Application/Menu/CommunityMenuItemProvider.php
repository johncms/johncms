<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\Menu;

use Johncms\Counters;
use Johncms\View\Menu\MenuArea;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuItemProviderInterface;

/**
 * The members of the site in the main menu, with those who joined in the last day.
 */
final readonly class CommunityMenuItemProvider implements MenuItemProviderInterface
{
    public function __construct(private Counters $counters)
    {
    }

    public function menuItems(): iterable
    {
        yield new MenuItem(
            MenuArea::Main,
            d__('community', 'Users'),
            '/community/',
            icon: 'users',
            weight: 60,
            counter: fn (): int => (int) $this->counters->usersCounters()['new'],
        );
    }
}
