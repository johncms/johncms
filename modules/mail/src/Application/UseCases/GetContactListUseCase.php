<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Mail\Application\DTO\ContactItemDTO;
use Johncms\Modules\Mail\Application\DTO\ContactListResultDTO;
use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;
use Johncms\Users\User;

final readonly class GetContactListUseCase
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private MailMessageRepositoryInterface $mailMessageRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $page = 1, int $perPage = 20): ContactListResultDTO
    {
        $paginator = $this->contactRepository->paginateContacts($this->currentUser->id, $perPage);
        $items = $this->mapContactsToDTO($paginator);

        $filters = [
            'all' => [
                'name' => d__('mail', 'My Contacts'),
                'url' => '/mail/',
                'active' => true,
            ],
            'positive' => [
                'name' => d__('mail', 'Blocklist'),
                'url' => '/mail/blocklist',
                'active' => false,
            ],
        ];

        return new ContactListResultDTO(
            items: $items,
            total: $paginator->total(),
            pagination: $paginator->render(),
            filters: $filters,
            backUrl: '../profile/?act=office',
        );
    }

    /**
     * @param LengthAwarePaginator $paginator
     * @return \Illuminate\Support\Collection<int, ContactItemDTO>
     */
    private function mapContactsToDTO(LengthAwarePaginator $paginator): \Illuminate\Support\Collection
    {
        $items = collect();
        foreach ($paginator->items() as $contact) {
            $contactUser = $contact->contactUser;
            if ($contactUser === null) {
                continue;
            }

            $countMessage = $this->mailMessageRepository->countMessagesBetween($this->currentUser->id, $contactUser->id);
            $newCountMessage = $this->mailMessageRepository->countNewMessagesFrom($this->currentUser->id, $contactUser->id);

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
                'url' => '?act=write&amp;id=' . $contactId,
                'name' => d__('mail', 'Correspondence'),
            ],
            [
                'url' => '/mail/delete-contact/' . $contactId,
                'name' => d__('mail', 'Delete'),
            ],
            [
                'url' => '/mail/block/' . $contactId,
                'name' => d__('mail', 'Block User'),
            ],
        ];
    }
}
