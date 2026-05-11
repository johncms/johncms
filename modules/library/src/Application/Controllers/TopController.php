<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Library\Hashtags;
use Library\Rating;
use PDO;

final readonly class TopController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private Tools $tools,
        private PDO $db,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(): string
    {
        $sort = $this->request->getQuery('sort', 'read');
        $sort = in_array($sort, ['read', 'rating', 'comm'], true) ? $sort : 'read';

        $pageTitle = __('Rating articles');
        $meta = new PageMeta($pageTitle . ' — ' . __('Library'), 1);

        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add($pageTitle);

        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        $filters = [
            'read'   => ['name' => __('Most readings'), 'url' => '/library/top',             'active' => $sort === 'read'],
            'rating' => ['name' => __('By rating'),     'url' => '/library/top?sort=rating', 'active' => $sort === 'rating'],
            'comm'   => ['name' => __('By comments'),   'url' => '/library/top?sort=comm',   'active' => $sort === 'comm'],
        ];

        $field = $sort === 'comm' ? '`comm_count`' : '`count_views`';

        if ($sort === 'read' || $sort === 'comm') {
            $total = $this->db->query('SELECT COUNT(*) FROM `library_texts` WHERE ' . $field . ' > 0')->fetchColumn();
        } else {
            $total = $this->db->query('SELECT COUNT(*) FROM `cms_library_rating`')->fetchColumn(0);
        }

        $req = null;
        if ($total) {
            if ($sort === 'read' || $sort === 'comm') {
                $req = $this->db->query(
                    'SELECT `id`, `name`, `time`, `uploader`, `uploader_id`, `count_views`, `cat_id`, `comments`, `comm_count`, `announce`
                    FROM `library_texts`
                    WHERE ' . $field . ' > 0
                    ORDER BY ' . $field . ' DESC
                    LIMIT 20'
                );
            } else {
                $req = $this->db->query(
                    'SELECT `t`.*, `r`.`cnt`, `r`.`avg`
                    FROM `library_texts` `t`
                    JOIN (
                        SELECT `st_id`, COUNT(*) AS `cnt`, AVG(`point`) AS `avg`
                        FROM `cms_library_rating`
                        GROUP BY `st_id`
                    ) `r` ON `r`.`st_id` = `t`.`id`
                    ORDER BY `r`.`avg` DESC, `r`.`cnt` DESC
                    LIMIT 20'
                );
            }
        }

        $tools = $this->tools;
        $db = $this->db;

        return $this->render->render('library::top', [
            'data'  => ['filters' => $filters],
            'total' => $total,
            'list'  => static function () use ($req, $tools, $db) {
                while ($res = $req->fetch()) {
                    $res['cover'] = file_exists(UPLOAD_PATH . 'library/images/small/' . $res['id'] . '.png');

                    $obj = new Hashtags($res['id']);
                    $res['tags'] = $obj->getAllStatTags() ? $obj->getAllStatTags(1) : null;

                    $rate = new Rating($res['id']);
                    $res['ratingView'] = $rate->viewRate(1);

                    $uploader = $res['uploader_id']
                        ? '<a href="' . config('johncms')['homeurl'] . '/profile/?user=' . $res['uploader_id'] . '">' . $tools->checkout($res['uploader']) . '</a>'
                        : $tools->checkout($res['uploader']);

                    $res['who'] = $uploader . ' (' . $tools->displayDate($res['time']) . ')';
                    $res['cat_name'] = $tools->checkout($db->query('SELECT `name` FROM `library_cats` WHERE `id` = ' . $res['cat_id'])->fetchColumn());
                    $res['name'] = $tools->checkout($res['name']);
                    $res['announce'] = $tools->checkout($res['announce'], 0, 0);

                    yield $res;
                }
            },
        ]);
    }
}
