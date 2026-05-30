<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Illuminate\Pagination\LengthAwarePaginator;
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

    public function execute(int $perPage): FileListResultDTO
    {
        $paginator = $this->mailMessageRepository->getAttachedFiles($this->currentUser->id, $perPage);

        return new FileListResultDTO(
            items: $this->mapToDTO($paginator),
            total: $paginator->total(),
            pagination: $paginator->render(),
            backUrl: '/profile/?act=office',
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, FileItemDTO>
     */
    private function mapToDTO(LengthAwarePaginator $paginator): \Illuminate\Support\Collection
    {
        $items = collect();

        foreach ($paginator->items() as $message) {
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
