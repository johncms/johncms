<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Johncms\Modules\Forum\Domain\Repository\ForumSearchRepositoryInterface;
use PDO;

final readonly class ForumSearchRepository implements ForumSearchRepositoryInterface
{
    public function __construct(
        private PDO $db,
    ) {
    }

    public function countTopicsByName(string $search, bool $includeDeleted): int
    {
        $sql = 'SELECT COUNT(*) FROM `forum_topic` WHERE `name` LIKE ?';
        if (! $includeDeleted) {
            $sql .= " AND (`deleted` != '1' OR `deleted` IS NULL)";
        }

        $query = $this->db->prepare($sql);
        $query->execute(['%' . $search . '%']);

        return (int) $query->fetchColumn();
    }

    public function getTopicsByName(string $search, bool $includeDeleted, int $start, int $limit): array
    {
        $sql = 'SELECT * FROM `forum_topic` WHERE `name` LIKE ?';
        if (! $includeDeleted) {
            $sql .= " AND (`deleted` != '1' OR `deleted` IS NULL)";
        }
        $sql .= ' ORDER BY `name` DESC LIMIT ?, ?';

        $query = $this->db->prepare($sql);
        $query->bindValue(1, '%' . $search . '%', PDO::PARAM_STR);
        $query->bindValue(2, max(0, $start), PDO::PARAM_INT);
        $query->bindValue(3, max(1, $limit), PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countMessagesByText(string $search, bool $includeDeleted): int
    {
        $sql = 'SELECT COUNT(*) FROM `forum_messages` WHERE MATCH (`text`) AGAINST (? IN BOOLEAN MODE)';
        if (! $includeDeleted) {
            $sql .= " AND (`deleted` != '1' OR `deleted` IS NULL)";
        }

        $query = $this->db->prepare($sql);
        $query->execute([$search]);

        return (int) $query->fetchColumn();
    }

    public function getMessagesByText(string $search, bool $includeDeleted, int $start, int $limit): array
    {
        $sql = '
            SELECT `frm`.*, `frt`.`name` AS `topic_name`, MATCH (`frm`.`text`) AGAINST (? IN BOOLEAN MODE) AS `rel`
            FROM `forum_messages` AS `frm`
            LEFT JOIN `forum_topic` AS `frt` ON `frt`.`id` = `frm`.`topic_id`
            WHERE MATCH (`frm`.`text`) AGAINST (? IN BOOLEAN MODE)
        ';
        if (! $includeDeleted) {
            $sql .= " AND (`frm`.`deleted` != '1' OR `frm`.`deleted` IS NULL)";
        }
        $sql .= ' ORDER BY `rel` DESC LIMIT ?, ?';

        $query = $this->db->prepare($sql);
        $query->bindValue(1, $search, PDO::PARAM_STR);
        $query->bindValue(2, $search, PDO::PARAM_STR);
        $query->bindValue(3, max(0, $start), PDO::PARAM_INT);
        $query->bindValue(4, max(1, $limit), PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
