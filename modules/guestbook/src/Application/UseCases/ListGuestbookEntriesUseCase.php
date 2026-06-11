<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Modules\Guestbook\Application\Access\GuestbookMode;
use Johncms\Modules\Guestbook\Application\DTO\GuestbookEntryDTO;
use Johncms\Modules\Guestbook\Application\DTO\GuestbookEntryMetaDTO;
use Johncms\Modules\Guestbook\Application\DTO\GuestbookEntryUserDTO;
use Johncms\Modules\Guestbook\Application\Services\GuestbookEntryTextFormatter;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use Johncms\Users\User;

final readonly class ListGuestbookEntriesUseCase
{
    public function __construct(
        private GuestbookEntryRepositoryInterface $repository,
        private User $currentUser,
        private GuestbookMode $mode,
        private GuestbookEntryTextFormatter $textFormatter,
    ) {
    }

    public function count(): int
    {
        return $this->repository->countEntries($this->mode->isAdminClub());
    }

    /**
     * @return GuestbookEntryDTO[]
     */
    public function getPage(int $limit, int $offset): array
    {
        $entries = $this->repository->getEntries($this->mode->isAdminClub(), $limit, $offset);

        return $entries->map(function (GuestbookEntry $entry) {
            return new GuestbookEntryDTO(
                id:        $entry->id,
                name:      $entry->name,
                isOnline:  $entry->is_online,
                createdAt: $entry->time,
                updateAt:  (string) $entry->edit_time,
                editCount: $entry->edit_count,
                updatedBy: $entry->edit_who,
                text:      $this->textFormatter->formatPost($entry),
                replyText: $this->textFormatter->formatReply($entry),
                repliedBy: $entry->admin,
                repliedAt: (string) $entry->otime,
                userId:    $entry->user_id,
                user:      $this->getUser($entry),
                meta:      $this->getMeta($entry),
            );
        })->all();
    }

    private function getUser(GuestbookEntry $entry): ?GuestbookEntryUserDTO
    {
        $user = $entry->user;
        if (! $user) {
            return null;
        }

        return new GuestbookEntryUserDTO(
            id:         $user->id,
            profileUrl: $user->profile_url,
            rightsName: $user->rights_name,
            rights:     $user->rights,
            status:     $user->status,
        );
    }

    private function getMeta(GuestbookEntry $entry): ?GuestbookEntryMetaDTO
    {
        if ($this->currentUser->rights < 1) {
            return null;
        }

        $canManage = $entry->user === null || $this->currentUser->rights >= $entry->user->rights;

        return new GuestbookEntryMetaDTO(
            ip:          $entry->ip,
            searchIpUrl: '/admin/ip-search?ip=' . $entry->ip,
            userAgent:   $entry->browser,
            canManage:   $canManage,
            editUrl:     $canManage ? '/guestbook/edit?id=' . $entry->id : null,
            deleteUrl:   $canManage ? '/guestbook/delpost?id=' . $entry->id : null,
            replyUrl:    $canManage ? '/guestbook/otvet?id=' . $entry->id : null,
        );
    }
}
