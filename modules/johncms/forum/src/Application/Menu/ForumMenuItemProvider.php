<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Menu;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\View\Menu\MenuArea;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuItemProviderInterface;

/**
 * The forum in the main menu, with the topics a member has not read; a guest has read nothing and
 * is shown no number.
 */
final readonly class ForumMenuItemProvider implements MenuItemProviderInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
        private ForumTopicRepositoryInterface $topics,
    ) {
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
            counter: fn (): int => $this->currentUser->isValid()
                ? $this->topics->countUnreadForUser(
                    $this->currentUser->id(),
                    $this->accessChecker->allows(ForumPermissions::DELETED_VIEW)
                )
                : 0,
        );
    }
}
