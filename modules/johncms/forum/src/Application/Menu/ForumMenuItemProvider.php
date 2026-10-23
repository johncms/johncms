<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Menu;

use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Services\UnreadTopicsCounter;
use Johncms\View\Menu\MenuArea;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuItemProviderInterface;

/**
 * The forum in the main menu, with the topics a member has not read; a guest has read nothing and
 * is shown no number.
 */
final readonly class ForumMenuItemProvider implements MenuItemProviderInterface
{
    public function __construct(private UnreadTopicsCounter $unreadTopics)
    {
    }

    public function menuItems(): iterable
    {
        yield new MenuItem(
            MenuArea::Main,
            d__('forum', 'Forum'),
            '/forum/',
            icon: 'forum',
            permission: ForumPermissions::VIEW,
            weight: 20,
            counter: $this->unreadTopics->count(...),
        );
    }
}
