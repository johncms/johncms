<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Modules\Guestbook\Application\DTO\CreateGuestbookEntryDTO;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use Johncms\Users\Repository\UserRepositoryInterface;
use Johncms\Users\User;

final readonly class CreateGuestbookEntryUseCase
{
    public function __construct(
        private GuestbookEntryRepositoryInterface $repository,
        private UserRepositoryInterface $userRepository,
        private User $currentUser,
    ) {
    }

    public function execute(CreateGuestbookEntryDTO $dto): GuestbookEntry
    {
        $entry = $this->repository->create(
            [
                'adm'            => $dto->adminClub,
                'time'           => time(),
                'user_id'        => $this->currentUser->id ?? 0,
                'name'           => $dto->name,
                'text'           => $dto->text,
                'ip'             => $dto->ip,
                'browser'        => $dto->userAgent,
                'otvet'          => '',
                'attached_files' => $dto->attachedFiles,
            ]
        );

        if ($this->currentUser->isValid()) {
            $this->userRepository->registerGuestbookPost($this->currentUser);
        }

        return $entry;
    }
}
