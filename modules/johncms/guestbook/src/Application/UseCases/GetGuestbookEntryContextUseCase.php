<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookEntryNotFoundException;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;

final readonly class GetGuestbookEntryContextUseCase
{
    public function __construct(
        private GuestbookEntryRepositoryInterface $repository,
    ) {
    }

    public function execute(int $id): GuestbookEntry
    {
        $entry = $this->repository->find($id);
        if ($entry === null) {
            throw new GuestbookEntryNotFoundException();
        }

        return $entry;
    }
}
