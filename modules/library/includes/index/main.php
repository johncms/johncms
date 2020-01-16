<?php

use Library\Utils;
use Library\ViewHelper;

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\View\Render $view
 */

$premod = false;
$countPremod = false;

if ($adm) {
    $countPremod = $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod` = 0')->fetchColumn();
    $premod = $countPremod > 0;
}

$new = $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `time` > ' . (time() - 259200) . ' AND `premod` = 1')->fetchColumn();

$total = $db->query('SELECT COUNT(*) FROM `library_cats` WHERE `parent` = 0')->fetchColumn();

$req = $db->query('SELECT `id`, `name`, `dir`, `description` FROM `library_cats` WHERE `parent` = 0 ORDER BY `pos` ASC');

$i = 0;

echo $view->render(
    'library::main',
    [
        'title'       => $title,
        'page_title'  => $title,
        'total'       => $total,
        'admin'       => $adm,
        'id'          => $id,
        'premod'      => $premod,
        'countPremod' => $countPremod,
        'new'         => $new,
        'list'        =>
            static function () use ($req, $tools, $id, $i, $total) {
                while ($res = $req->fetch()) {
                    $i++;
                    $res['name'] = $tools->checkout($res['name']);
                    $res['libCounter'] = Utils::libCounter($res['id'], $res['dir']);
                    $res['description'] = ! empty($res['description']) ? $tools->checkout($res['description']) : null;
                    $res['sectionListAdminPanel'] = ViewHelper::sectionsListAdminPanel($id, $res['id'], $i, $total);

                    yield $res;
                }
            },
    ]
);
