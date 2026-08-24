<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Http\Session;

final readonly class SetFilterByAuthorUseCase
{
    public function __construct(
        private Session $session,
    ) {
    }

    /**
     * @param array<int, mixed> $userIds
     */
    public function execute(int $topicId, array $userIds): void
    {
        $normalizedUserIds = [];
        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            if ($userId > 0) {
                $normalizedUserIds[$userId] = $userId;
            }
        }

        $this->session->set('fsort_id', $topicId);
        $this->session->set('fsort_users', array_values($normalizedUserIds));
    }
}
