<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Modules\Guestbook\Application\Access\GuestbookMode;
use Johncms\Modules\Guestbook\Application\DTO\GuestbookEntryPlaceDTO;
use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookEntryNotFoundException;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;

/**
 * Finds the page an entry is on at the moment: new entries push it further with every one written,
 * so the page is counted when the entry is asked for, never stored.
 */
final readonly class LocateGuestbookEntryUseCase
{
    public function __construct(
        private GuestbookEntryRepositoryInterface $repository,
        private GuestbookMode $mode,
    ) {
    }

    /**
     * @throws GuestbookEntryNotFoundException when there is no such entry, or it is in the admin
     *     club and the visitor is not let into it: an entry of the club is not admitted to exist.
     */
    public function execute(int $id, int $perPage): GuestbookEntryPlaceDTO
    {
        $entry = $this->repository->find($id);
        if ($entry === null || ! $this->mode->canEnter($entry->adm)) {
            throw new GuestbookEntryNotFoundException();
        }

        return new GuestbookEntryPlaceDTO(
            id:        $entry->id,
            adminClub: $entry->adm,
            page:      intdiv($this->repository->countEntriesAbove($entry), max(1, $perPage)) + 1,
        );
    }
}
