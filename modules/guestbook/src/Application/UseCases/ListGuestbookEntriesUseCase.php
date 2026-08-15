<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Guestbook\Application\Access\GuestbookMode;
use Johncms\Modules\Guestbook\Application\DTO\GuestbookEntryDTO;
use Johncms\Modules\Guestbook\Application\DTO\GuestbookEntryMetaDTO;
use Johncms\Modules\Guestbook\Application\DTO\GuestbookEntryUserDTO;
use Johncms\Modules\Guestbook\Application\Services\GuestbookEntryTextFormatter;
use Johncms\Modules\Guestbook\Application\Services\GuestbookPermissions;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use Johncms\Users\User;

final readonly class ListGuestbookEntriesUseCase
{
    public function __construct(
        private GuestbookEntryRepositoryInterface $repository,
        private CurrentUser $currentUser,
        private GuestbookMode $mode,
        private GuestbookEntryTextFormatter $textFormatter,
        private AccessCheckerInterface $accessChecker,
        private RoleLevels $roleLevels,
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

        // The whole page asks about the standing of its authors at once, rather than a query per
        // row: the answer decides which entries carry the buttons of the staff.
        $authorIds = $entries
            ->map(static fn (GuestbookEntry $entry): ?int => $entry->user?->id)
            ->filter()
            ->values()
            ->all();
        $authorLevels = $this->roleLevels->highestGrantedToMany($authorIds);

        return $entries->map(function (GuestbookEntry $entry) use ($authorLevels) {
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
                meta:      $this->getMeta($entry, $authorLevels),
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

    /**
     * @param array<int, int> $authorLevels
     */
    private function getMeta(GuestbookEntry $entry, array $authorLevels): ?GuestbookEntryMetaDTO
    {
        $canSeeOrigin = $this->accessChecker->allows(CorePermissions::USERS_ORIGIN_VIEW);
        $canManage = $this->accessChecker->allows(GuestbookPermissions::ENTRY_MANAGE)
            && $this->standsAtOrBelow($entry, $authorLevels);

        if (! $canSeeOrigin && ! $canManage) {
            return null;
        }

        return new GuestbookEntryMetaDTO(
            ip:          $canSeeOrigin ? $entry->ip : null,
            searchIpUrl: $canSeeOrigin ? '/admin/ip-search?ip=' . $entry->ip : null,
            userAgent:   $canSeeOrigin ? $entry->browser : null,
            canManage:   $canManage,
            editUrl:     $canManage ? '/guestbook/edit?id=' . $entry->id : null,
            deleteUrl:   $canManage ? '/guestbook/delpost?id=' . $entry->id : null,
            // The reply of the staff is a permission of its own, and the screen behind the link
            // asks for it.
            replyUrl:    $canManage && $this->accessChecker->allows(GuestbookPermissions::ENTRY_REPLY)
                ? '/guestbook/otvet?id=' . $entry->id
                : null,
        );
    }

    /**
     * Whether the author of the entry stands no higher than the visitor. An entry left by a
     * guest belongs to nobody and is managed by any of the staff.
     *
     * @param array<int, int> $authorLevels
     */
    private function standsAtOrBelow(GuestbookEntry $entry, array $authorLevels): bool
    {
        $author = $entry->user;

        if ($author === null) {
            return true;
        }

        return ($authorLevels[$author->id] ?? 0) <= $this->roleLevels->highest($this->currentUser->identity());
    }
}
