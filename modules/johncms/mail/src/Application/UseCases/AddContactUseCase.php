<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Mail\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Mail\Application\Exceptions\CannotAddYourselfException;
use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Users\User;

final readonly class AddContactUseCase
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(int $contactId): void
    {
        $contactUser = User::query()->find($contactId);
        if ($contactUser === null) {
            throw new UserNotFoundException('User does not exists');
        }

        if ($contactUser->id === $this->currentUser->id()) {
            throw new CannotAddYourselfException('You cannot add yourself as a contact');
        }

        $existingContact = $this->contactRepository->findContact($this->currentUser->id(), $contactId);
        if ($existingContact === null) {
            $this->contactRepository->addContact($this->currentUser->id(), $contactId);
        }
        // If contact already exists (maybe blocked), do nothing
    }
}
