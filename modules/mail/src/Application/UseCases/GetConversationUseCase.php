<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Modules\Mail\Application\DTO\ConversationResultDTO;
use Johncms\Modules\Mail\Application\DTO\MessageItemDTO;
use Johncms\Modules\Mail\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Mail\Application\Services\MailFileService;
use Johncms\Modules\Mail\Domain\Models\MailMessage;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;
use Johncms\System\Legacy\Tools;
use Johncms\UserProperties;
use Johncms\Users\User;
use Simba77\EmbedMedia\Embed;

final readonly class GetConversationUseCase
{
    public function __construct(
        private MailMessageRepositoryInterface $mailMessageRepository,
        private MailFileService $mailFileService,
        private UserProperties $userProperties,
        private Tools $tools,
        private \HTMLPurifier $purifier,
        private Embed $media,
        private User $currentUser,
    ) {
    }

    public function count(int $contactId): int
    {
        $this->ensureContactExists($contactId);

        return $this->mailMessageRepository->countConversation($this->currentUser->id, $contactId);
    }

    public function getPage(int $contactId, int $limit, int $offset): ConversationResultDTO
    {
        $this->ensureContactExists($contactId);

        $messages = $this->mailMessageRepository->getConversation($this->currentUser->id, $contactId, $limit, $offset);

        $items = $this->mapToDTO($messages);
        $this->markIncomingAsRead($messages);

        $canWrite = empty($this->currentUser->ban['1'])
            && empty($this->currentUser->ban['3'])
            && ! $this->tools->isIgnor($contactId);

        return new ConversationResultDTO(
            items: $items,
            backUrl: '/profile/account',
            clearUrl: '/mail/clear/' . $contactId,
            formAction: $canWrite ? '/mail/write/' . $contactId : null,
            showNickInput: false,
            nick: '',
        );
    }

    private function ensureContactExists(int $contactId): void
    {
        if (User::query()->find($contactId) === null) {
            throw new UserNotFoundException();
        }
    }

    /**
     * @param \Illuminate\Support\Collection<int, MailMessage> $messages
     * @return \Illuminate\Support\Collection<int, MessageItemDTO>
     */
    private function mapToDTO(\Illuminate\Support\Collection $messages): \Illuminate\Support\Collection
    {
        $items = collect();

        foreach ($messages as $message) {
            if (! $message instanceof MailMessage) {
                continue;
            }

            // The message author (the user who wrote it) is stored in user_id.
            $author = $message->recipient;
            $authorRights = $author->rights ?? 0;

            $userData = $author !== null
                ? $this->userProperties->getFromArray(array_merge($author->getRawOriginal(), ['user_id' => $message->user_id]))
                : [];

            $text = $this->purifier->purify($message->text);
            $text = $this->media->embedMedia($text);
            $text = $this->tools->smilies($text, $authorRights >= 1 ? 1 : 0);

            $files = [];
            if ($message->file_name) {
                $files[] = [
                    'file_size' => $this->mailFileService->formatSize($message->size),
                    'file_url'  => '/mail/load/' . $message->id,
                    'dlcount'   => $message->count,
                    'filename'  => $message->file_name,
                ];
            }

            $items->push(new MessageItemDTO(
                messageId: $message->id,
                userId: $message->user_id,
                name: $author->name ?? '',
                read: $message->read,
                text: $text,
                displayDate: $this->tools->displayDate($message->time),
                userIsOnline: $userData['user_is_online'] ?? false,
                userProfileLink: $userData['user_profile_link'] ?? '',
                userRightsName: $userData['user_rights_name'] ?? '',
                ip: $userData['ip'] ?? '',
                searchIpUrl: $userData['search_ip_url'] ?? '',
                ipViaProxy: $userData['ip_via_proxy'] ?? '',
                searchIpViaProxyUrl: $userData['search_ip_via_proxy_url'] ?? '',
                browser: htmlspecialchars((string) ($author->browser ?? '')),
                deleteUrl: '/mail/delete/' . $message->id,
                files: $files,
            ));
        }

        return $items;
    }

    /**
     * Mark messages received by the current user (within this conversation page) as read.
     *
     * @param \Illuminate\Support\Collection<int, MailMessage> $messages
     */
    private function markIncomingAsRead(\Illuminate\Support\Collection $messages): void
    {
        $ids = [];
        foreach ($messages as $message) {
            if ($message instanceof MailMessage && ! $message->read && $message->from_id === $this->currentUser->id) {
                $ids[] = $message->id;
            }
        }

        $this->mailMessageRepository->markAsReadByIds($ids);
    }
}
