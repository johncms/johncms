<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Mail\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Mail\Application\Exceptions\CannotAddYourselfException;
use Johncms\Modules\Mail\Application\Exceptions\ContactAlreadyExistsException;
use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Users\User;

final readonly class GetAddContactContextUseCase
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(int $contactId): User
    {
        $contactUser = User::query()->find($contactId);
        if ($contactUser === null) {
            throw new UserNotFoundException('User does not exists');
        }

        if ($contactUser->id === $this->currentUser->id()) {
            throw new CannotAddYourselfException('You cannot add yourself as a contact');
        }

        $existingContact = $this->contactRepository->findContact($this->currentUser->id(), $contactId);
        if ($existingContact !== null && $existingContact->ban === false) {
            throw new ContactAlreadyExistsException('User has been added to your contact list');
        }

        return $contactUser;
    }
}
