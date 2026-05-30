<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Illuminate\Pagination\LengthAwarePaginator;
use Johncms\Modules\Mail\Application\DTO\ConversationItemDTO;
use Johncms\Modules\Mail\Application\DTO\ConversationListResultDTO;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;
use Johncms\System\Legacy\Bbcode;
use Johncms\System\Utility\Tools;
use Johncms\UserProperties;
use Johncms\Users\User;

final readonly class GetIncomingConversationsUseCase
{
    public function __construct(
        private MailMessageRepositoryInterface $mailMessageRepository,
        private User $currentUser,
        private UserProperties $userProperties,
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
            backUrl: '/profile/?act=office',
        );
    }

    /**
     * @param LengthAwarePaginator $paginator
     * @return \Illuminate\Support\Collection<int, ConversationItemDTO>
     */
    private function mapToDTO(LengthAwarePaginator $paginator): \Illuminate\Support\Collection
    {
        $items = collect();
        $tools = di('tools');
        $bbcode = di('bbcode');

        foreach ($paginator->items() as $user) {
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
                $text = $lastMessage->text;
                if (mb_strlen($text) > 500) {
                    $text = mb_substr($text, 0, 500);
                    $text = $tools->checkout($text, 1, 1);
                    $text = $tools->smilies($text, $user->rights ? 1 : 0);
                    $text = $bbcode->notags($text);
                    $previewText = $text . '...<a href="/mail/write/' . $user->id . '">' . __('Continue') . ' &gt;&gt;</a>';
                } else {
                    $previewText = $tools->checkout($text, 1, 1);
                    $previewText = $tools->smilies($previewText, $user->rights ? 1 : 0);
                }

                $displayDate = $tools->displayDate($lastMessage->time);
                $unread = ! $lastMessage->read;
            }

            $userData = $this->userProperties->getFromArray($user->toArray());
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
                previewText: $previewText,
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
