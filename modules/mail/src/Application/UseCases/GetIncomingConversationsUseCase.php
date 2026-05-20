<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Illuminate\Pagination\LengthAwarePaginator;
use Johncms\Modules\Mail\Application\DTO\ConversationItemDTO;
use Johncms\Modules\Mail\Application\DTO\ConversationListResultDTO;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;
use Johncms\Users\User;

final readonly class GetIncomingConversationsUseCase
{
    public function __construct(
        private MailMessageRepositoryInterface $mailMessageRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $page = 1, int $perPage = 20): ConversationListResultDTO
    {
        $paginator = $this->mailMessageRepository->getIncomingConversations($this->currentUser->id, $perPage, $page);
        $items = $this->mapToDTO($paginator);

        return new ConversationListResultDTO(
            items: $items,
            total: $paginator->total(),
            pagination: $paginator->render(),
            backUrl: '../profile/?act=office',
        );
    }

    /**
     * @param LengthAwarePaginator $paginator
     * @return \Illuminate\Support\Collection<int, ConversationItemDTO>
     */
    private function mapToDTO(LengthAwarePaginator $paginator): \Illuminate\Support\Collection
    {
        // TODO: Implement proper mapping
        return collect();
    }
}
