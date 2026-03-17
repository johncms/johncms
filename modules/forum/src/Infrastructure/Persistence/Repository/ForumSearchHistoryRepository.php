<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Johncms\Modules\Forum\Domain\Repository\ForumSearchHistoryRepositoryInterface;
use PDO;

final readonly class ForumSearchHistoryRepository implements ForumSearchHistoryRepositoryInterface
{
    private const SEARCH_HISTORY_KEY = 'forum_search';

    public function __construct(
        private PDO $db,
    ) {
    }

    public function getByUserId(int $userId): array
    {
        $query = $this->db->prepare(
            'SELECT `val` FROM `cms_users_data` WHERE `user_id` = ? AND `key` = ? ORDER BY `id` DESC LIMIT 1'
        );
        $query->execute([$userId, self::SEARCH_HISTORY_KEY]);
        $value = $query->fetchColumn();

        if (! is_string($value) || $value === '') {
            return [];
        }

        $history = unserialize($value, ['allowed_classes' => false]);

        return is_array($history) ? array_values(array_filter($history, 'is_string')) : [];
    }

    public function saveForUser(int $userId, array $history): void
    {
        $this->clearForUser($userId);

        $query = $this->db->prepare(
            '
            INSERT INTO `cms_users_data` (`user_id`, `key`, `val`)
            VALUES (?, ?, ?)
            '
        );
        $query->execute([$userId, self::SEARCH_HISTORY_KEY, serialize($history)]);
    }

    public function clearForUser(int $userId): void
    {
        $query = $this->db->prepare(
            'DELETE FROM `cms_users_data` WHERE `user_id` = ? AND `key` = ?'
        );
        $query->execute([$userId, self::SEARCH_HISTORY_KEY]);
    }
}
