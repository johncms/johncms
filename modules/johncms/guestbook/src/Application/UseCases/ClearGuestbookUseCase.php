<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Database\TransactionManagerInterface;
use Johncms\EditorUploads\EditorUploads;
use Johncms\Modules\Guestbook\Application\Uploads\GuestbookEditorUploads;
use Johncms\Modules\Guestbook\Domain\Enums\ClearGuestbookPeriod;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;

final readonly class ClearGuestbookUseCase
{
    public function __construct(
        private GuestbookEntryRepositoryInterface $repository,
        private EditorUploads $uploads,
        private TransactionManagerInterface $transactions,
    ) {
    }

    /** The entries of the period go with their pictures; the files are removed once the deletion commits. */
    public function execute(bool $adminClub, ClearGuestbookPeriod $period): void
    {
        $maxAge = $period->maxAge();
        $olderThan = $maxAge !== null ? time() - $maxAge : null;

        $this->transactions->run(function () use ($adminClub, $olderThan): void {
            $ids = $this->repository->getIdsToClear($adminClub, $olderThan);
            foreach (array_chunk($ids, 500) as $chunk) {
                $this->repository->deleteByIds($chunk);
                $this->uploads->detach(GuestbookEditorUploads::OWNER_TYPE, $chunk);
            }
        });
    }
}
