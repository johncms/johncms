<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Illuminate\Support\Collection;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Mail\Application\DTO\ContactItemDTO;
use Johncms\Modules\Mail\Application\DTO\ContactListResultDTO;
use Johncms\Modules\Mail\Domain\Models\Contact;
use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;

final readonly class GetContactListUseCase
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private MailMessageRepositoryInterface $mailMessageRepository,
        private CurrentUser $currentUser,
    ) {
    }

    public function count(): int
    {
        return $this->contactRepository->countContactList($this->currentUser->id());
    }

    public function getPage(int $limit, int $offset): ContactListResultDTO
    {
        $contacts = $this->contactRepository->getContactList($this->currentUser->id(), $limit, $offset);

        $filters = [
            'all' => [
                'name' => __('My Contacts'),
                'url' => '/mail/contacts',
                'active' => true,
            ],
            'positive' => [
                'name' => __('Blocklist'),
                'url' => '/mail/blocklist',
                'active' => false,
            ],
        ];

        return new ContactListResultDTO(
            items: $this->mapContactsToDTO($contacts),
            filters: $filters,
            backUrl: '/profile/account',
        );
    }

    /**
     * @param Collection<int, Contact> $contacts
     * @return Collection<int, ContactItemDTO>
     */
    private function mapContactsToDTO(Collection $contacts): Collection
    {
        $items = collect();
        foreach ($contacts as $contact) {
            $contactUser = $contact->contactUser;
            if ($contactUser === null) {
                continue;
            }

            $countMessage = $this->mailMessageRepository->countMessagesBetween($this->currentUser->id(), $contactUser->id);
            $newCountMessage = $this->mailMessageRepository->countNewMessagesFrom($this->currentUser->id(), $contactUser->id);

            $items->push(new ContactItemDTO(
                id: $contactUser->id,
                name: $contactUser->name,
                countMessage: $countMessage,
                newCountMessage: $newCountMessage,
                userIsOnline: $contactUser->lastdate >= (time() - 300),
                buttons: $this->buildButtons($contactUser->id),
            ));
        }

        return $items;
    }

    private function buildButtons(int $contactId): array
    {
        return [
            [
                'url' => '/mail/write/' . $contactId,
                'name' => __('Correspondence'),
            ],
            [
                'url' => '/mail/delete-contact/' . $contactId,
                'name' => __('Delete'),
            ],
            [
                'url' => '/mail/block/' . $contactId,
                'name' => __('Block User'),
            ],
        ];
    }
}
