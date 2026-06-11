<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Illuminate\Support\Collection;
use Johncms\Modules\Mail\Application\DTO\FileItemDTO;
use Johncms\Modules\Mail\Application\DTO\FileListResultDTO;
use Johncms\Modules\Mail\Application\Services\MailFileService;
use Johncms\Modules\Mail\Domain\Models\MailMessage;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;
use Johncms\Users\User;

final readonly class GetAttachedFilesUseCase
{
    public function __construct(
        private MailMessageRepositoryInterface $mailMessageRepository,
        private MailFileService $mailFileService,
        private User $currentUser,
    ) {
    }

    public function count(): int
    {
        return $this->mailMessageRepository->countAttachedFiles($this->currentUser->id);
    }

    public function getPage(int $limit, int $offset): FileListResultDTO
    {
        $messages = $this->mailMessageRepository->getAttachedFiles($this->currentUser->id, $limit, $offset);

        return new FileListResultDTO(
            items: $this->mapToDTO($messages),
            backUrl: '/profile/account',
        );
    }

    /**
     * @param Collection<int, MailMessage> $messages
     * @return Collection<int, FileItemDTO>
     */
    private function mapToDTO(Collection $messages): Collection
    {
        $items = collect();

        foreach ($messages as $message) {
            if (! $message instanceof MailMessage) {
                continue;
            }

            $recipient = $message->recipient;

            $items->push(new FileItemDTO(
                messageId: $message->id,
                userId: $message->user_id,
                name: $recipient->name ?? '',
                userIsOnline: $recipient !== null && $recipient->lastdate >= (time() - 300),
                fileName: $message->file_name,
                fileSize: $this->mailFileService->formatSize($message->size),
                downloadCount: $message->count,
                downloadUrl: '/mail/load/' . $message->id,
                profileUrl: '/profile/' . $message->user_id,
            ));
        }

        return $items;
    }
}
