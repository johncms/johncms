<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Illuminate\Support\Collection;
use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Modules\Mail\Application\DTO\ConversationItemDTO;
use Johncms\Modules\Mail\Application\DTO\ConversationListResultDTO;
use Johncms\Modules\Mail\Application\Services\MailMessagePreviewService;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;
use Johncms\UserProperties;
use Johncms\Users\User;
use Johncms\Utils\DateFormatterInterface;
use Twig\Markup;

final readonly class GetOutgoingConversationsUseCase
{
    public function __construct(
        private StaffTitles $staffTitles,
        private MailMessageRepositoryInterface $mailMessageRepository,
        private User $currentUser,
        private UserProperties $userProperties,
        private DateFormatterInterface $dateFormatter,
        private MailMessagePreviewService $previewService,
    ) {
    }

    public function count(): int
    {
        return $this->mailMessageRepository->countOutgoingConversations($this->currentUser->id);
    }

    public function getPage(int $limit, int $offset): ConversationListResultDTO
    {
        $users = $this->mailMessageRepository->getOutgoingConversations($this->currentUser->id, $limit, $offset);

        return new ConversationListResultDTO(
            items: $this->mapToDTO($users),
            backUrl: '/profile/account',
        );
    }

    /**
     * @param Collection<int, User> $users
     * @return Collection<int, ConversationItemDTO>
     */
    private function mapToDTO(Collection $users): Collection
    {
        $items = collect();
        foreach ($users as $user) {
            if (! $user instanceof User) {
                continue;
            }

            $countMessage = $this->mailMessageRepository->countMessagesBetween($this->currentUser->id, $user->id);
            $messages = $this->mailMessageRepository->getMessagesBetween($this->currentUser->id, $user->id);
            $lastMessage = $messages->sortByDesc('time')->first();

            $previewText = '';
            $displayDate = '';
            $unread = false;

            if ($lastMessage) {
                $previewText = $this->previewService->render(
                    $lastMessage->text,
                    $user->id,
                    $this->staffTitles->isStaff((int) $user->id)
                );
                $displayDate = $this->dateFormatter->format($lastMessage->time);
                $unread = ! $lastMessage->read;
            }

            $userData = $this->userProperties->getFromArray($user->getRawOriginal());
            $userIsOnline = $user->lastdate >= (time() - 300);

            $buttons = [
                [
                    'url'  => '/mail/write/' . $user->id,
                    'name' => __('Correspondence'),
                ],
                [
                    'url'  => '/mail/delete-contact/' . $user->id,
                    'name' => __('Delete'),
                ],
                [
                    'url'  => '/mail/block/' . $user->id,
                    'name' => __('Block User'),
                ],
            ];

            $items->push(new ConversationItemDTO(
                id: $user->id,
                name: $user->name,
                countMessage: $countMessage,
                displayDate: $displayDate,
                previewText: new Markup($previewText, 'UTF-8'),
                unread: $unread,
                writeUrl: '/mail/write/' . $user->id,
                buttons: $buttons,
                userIsOnline: $userIsOnline,
                userRightsName: $userData['user_rights_name'] ?? null,
                status: $userData['status'] ?? null,
            ));
        }

        return $items;
    }
}
