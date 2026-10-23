<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Menu;

use Johncms\Modules\Guestbook\Application\Services\GuestbookPermissions;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use Johncms\View\Menu\MenuArea;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuItemProviderInterface;

/**
 * The guestbook in the main menu and its chat of the administration in the part of the staff,
 * each with the entries of the last day.
 */
final readonly class GuestbookMenuItemProvider implements MenuItemProviderInterface
{
    private const int DAY = 86400;

    public function __construct(private GuestbookEntryRepositoryInterface $entries)
    {
    }

    public function menuItems(): iterable
    {
        yield new MenuItem(
            MenuArea::Main,
            d__('guestbook', 'Guestbook'),
            '/guestbook/ga',
            icon: 'chat',
            permission: GuestbookPermissions::VIEW,
            weight: 30,
            counter: fn (): int => $this->entries->countEntriesSince(false, time() - self::DAY),
        );

        yield new MenuItem(
            MenuArea::Staff,
            d__('guestbook', 'Admin Chat'),
            '/guestbook/ga?do=set',
            icon: 'forum',
            permission: GuestbookPermissions::ADMIN_CLUB_VIEW,
            weight: 20,
            counter: fn (): int => $this->entries->countEntriesSince(true, time() - self::DAY),
        );
    }
}
