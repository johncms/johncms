<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var Render $view */

$view = di(Render::class);
/** @var PDO $db */
$db = di(PDO::class);
$nav_chain = di(NavChain::class);
/** @var Request $request */
$request = di(Request::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('redirect', __DIR__ . '/templates/');
$title = __('Redirect to an external link');
$nav_chain->add($title);

$id = $request->getQuery('id', 0, FILTER_VALIDATE_INT);
$url = isset($_REQUEST['url']) ? strip_tags(rawurldecode(trim($_REQUEST['url']))) : false;

if ($url) {
    // Редирект по ссылкам в текстах, обработанным функцией tags()
    if (isset($_POST['submit'])) {
        header('Location: ' . $url);
    } else {
        echo $view->render(
            'redirect::index',
            [
                'title'        => $title,
                'page_title'   => $title,
                'redirect_url' => rawurlencode($url),
                'referer'      => (isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : '/'),
                'url'          => $url,
            ]
        );
    }
} elseif ($id) {
    // Редирект по рекламной ссылке
    $req = $db->query("SELECT * FROM `cms_ads` WHERE `id` = '$id'");

    if ($req->rowCount()) {
        $res = $req->fetch();
        $count_link = $res['count'] + 1;
        $db->exec("UPDATE `cms_ads` SET `count` = '$count_link'  WHERE `id` = '$id'");
        header('Location: ' . $res['link']);
    } else {
        header('Location: https://johncms.com/404');
    }
}
