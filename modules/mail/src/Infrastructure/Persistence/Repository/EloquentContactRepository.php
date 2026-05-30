<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Mail\Domain\Models\Contact;
use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;

class EloquentContactRepository implements ContactRepositoryInterface
{
    public function getContacts(int $userId): Collection
    {
        return Contact::query()
            ->where('user_id', $userId)
            ->with('contactUser')
            ->get();
    }

    public function findContact(int $userId, int $contactId): ?Contact
    {
        return Contact::query()
            ->where('user_id', $userId)
            ->where('from_id', $contactId)
            ->first();
    }

    public function addContact(int $userId, int $contactId, array $data = []): Contact
    {
        $contact = $this->findContact($userId, $contactId);
        if ($contact !== null) {
            return $contact;
        }

        $contact = new Contact([
            'user_id' => $userId,
            'from_id' => $contactId,
            'time' => time(),
            'type' => $data['type'] ?? 1,
            'friends' => $data['friends'] ?? 0,
            'ban' => $data['ban'] ?? 0,
            'man' => $data['man'] ?? 0,
        ]);
        $contact->save();

        return $contact;
    }

    public function removeContact(int $userId, int $contactId): void
    {
        Contact::query()
            ->where('user_id', $userId)
            ->where('from_id', $contactId)
            ->delete();
    }

    public function updateContactTime(int $userId, int $contactId, int $time): void
    {
        Contact::query()
            ->where('user_id', $userId)
            ->where('from_id', $contactId)
            ->update(['time' => $time]);
    }

    public function blockUser(int $userId, int $blockedUserId): Contact
    {
        $contact = $this->findContact($userId, $blockedUserId);
        if ($contact === null) {
            $contact = $this->addContact($userId, $blockedUserId, ['ban' => 1]);
        } else {
            $contact->ban = 1;
            $contact->save();
        }

        return $contact;
    }

    public function unblockUser(int $userId, int $blockedUserId): void
    {
        Contact::query()
            ->where('user_id', $userId)
            ->where('from_id', $blockedUserId)
            ->update(['ban' => 0]);
    }

    public function getBlocklist(int $userId): Collection
    {
        return Contact::query()
            ->where('user_id', $userId)
            ->where('ban', 1)
            ->with('contactUser')
            ->get();
    }

    public function isBlocked(int $userId, int $contactId): bool
    {
        return Contact::query()
            ->where('user_id', $userId)
            ->where('from_id', $contactId)
            ->where('ban', 1)
            ->exists();
    }

    public function paginateContacts(int $userId, int $perPage): LengthAwarePaginator
    {
        return Contact::query()
            ->select('cms_contact.*')
            ->join('users', 'cms_contact.from_id', '=', 'users.id')
            ->where('cms_contact.user_id', $userId)
            ->where('cms_contact.ban', '!=', 1)
            ->orderBy('users.name')
            ->with('contactUser')
            ->paginate($perPage);
    }

    public function countContacts(int $userId): int
    {
        return Contact::query()
            ->where('user_id', $userId)
            ->where('ban', '!=', 1)
            ->count();
    }
}
