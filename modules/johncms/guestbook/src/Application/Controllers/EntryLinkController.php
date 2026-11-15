<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Modules\Guestbook\Application\Access\GuestbookMode;
use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookEntryNotFoundException;
use Johncms\Modules\Guestbook\Application\UseCases\LocateGuestbookEntryUseCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The lasting address of an entry. It names no page: the page is found when the address is opened,
 * so the link still leads to the entry however many have been written since.
 */
final readonly class EntryLinkController
{
    public function __construct(
        private CurrentUser $currentUser,
        private GuestbookMode $mode,
        private LocateGuestbookEntryUseCase $locate,
    ) {
    }

    public function __invoke(int $id): RedirectResponse
    {
        try {
            $place = $this->locate->execute($id, (int) $this->currentUser->user()->config->kmess);
        } catch (GuestbookEntryNotFoundException) {
            throw new PageNotFoundException();
        }

        // The guestbook a visitor reads is kept in the session; the link opens the one of the entry.
        $this->mode->enter($place->adminClub);

        return new RedirectResponse(
            '/guestbook/' . ($place->page > 1 ? '?page=' . $place->page : '') . '#guestbook-entry-' . $place->id
        );
    }
}
