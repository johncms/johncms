<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\NavChain;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use PDO;

final readonly class LatestCommentsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Tools $tools,
        private PDO $db,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(): string
    {
        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add(__('Latest comments'));

        $this->render->addData([
            'title'      => __('Latest comments'),
            'page_title' => __('Latest comments'),
        ]);

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
                'id'    => $row['id'],
                'name'  => $row['name'],
                'text'  => mb_substr(trim(strip_tags((string) $row['text'])), 0, 500),
                'who'   => $this->tools->checkout($row['user_name']) . ' (' . $this->tools->displayDate($row['time']) . ')',
                'image' => file_exists(UPLOAD_PATH . 'library/images/small/' . $row['id'] . '.png'),
            ];
        }

        return $this->render->render('library::lastcom', [
            'total' => count($list),
            'list'  => $list,
        ]);
    }
}
