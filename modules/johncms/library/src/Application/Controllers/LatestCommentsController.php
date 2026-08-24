<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Utils\DateFormatterInterface;
use PDO;

final readonly class LatestCommentsController
{
    public function __construct(
        private NavChain $navChain,
        private DateFormatterInterface $dateFormatter,
        private PDO $db,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add(__('Latest comments'));

        $stmt = $this->db->query('
            SELECT
                `comm`.`user_id`,
                `comm`.`text`,
                `txt`.`name`,
                `txt`.`comm_count`,
                `txt`.`id`,
                `comm`.`time`,
                `u`.`name` AS `user_name`
            FROM `cms_library_comments` `comm`
            JOIN `library_texts` `txt` ON `comm`.`sub_id` = `txt`.`id`
            JOIN `users` `u` ON `u`.`id` = `comm`.`user_id`
            JOIN (
                SELECT `sub_id`, MAX(`time`) AS `mtime`
                FROM `cms_library_comments`
                GROUP BY `sub_id`
            ) AS `comm2` ON `comm`.`sub_id` = `comm2`.`sub_id` AND `comm`.`time` = `comm2`.`mtime`
            ORDER BY `comm`.`time` DESC
            LIMIT 20
        ');

        $list = [];
        while ($row = $stmt->fetch()) {
            $list[] = [
                'article_id'   => $row['id'],
                'article_name' => $row['name'],
                'text'         => mb_substr(trim(strip_tags((string) $row['text'])), 0, 500),
                'user_id'      => $row['user_id'],
                'user_name'    => $row['user_name'],
                'date'         => $this->dateFormatter->format($row['time']),
            ];
        }

        return new ViewResponse('@library/public/latest-comments.twig', [
            'title'      => __('Latest comments'),
            'page_title' => __('Latest comments'),
            'total'      => count($list),
            'comments'   => $list,
        ]);
    }
}
