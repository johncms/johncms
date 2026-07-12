<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Domain\Repository;

use Illuminate\Support\Collection;
use Johncms\Modules\Contacts\Domain\Enums\ContactMessageStatus;
use Johncms\Modules\Contacts\Domain\Models\ContactMessage;

interface ContactMessageRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): ContactMessage;

    public function findById(int $id): ?ContactMessage;

    /**
     * @return Collection<int, ContactMessage>
     */
    public function getPage(int $limit, int $offset, ?ContactMessageStatus $status = null): Collection;

    public function count(?ContactMessageStatus $status = null): int;

    public function markProcessed(ContactMessage $message): void;

    public function delete(ContactMessage $message): void;
}
