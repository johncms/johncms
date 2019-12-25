<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Api\NavChainInterface;
use Johncms\System\Http\Request;
use Johncms\System\Users\User;
use Johncms\System\Utility\Tools;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Render;
use Zend\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Assets $assets
 * @var PDO $db
 * @var Tools $tools
 * @var User $user
 * @var Render $view
 */

$assets = di(Assets::class);
$db = di(PDO::class);
$user = di(User::class);
$tools = di(Tools::class);
$view = di(Render::class);

/** @var NavChainInterface $nav_chain */
$nav_chain = di(NavChainInterface::class);

/** @var Request $request */
$request = di(Request::class);

// Регистрируем папку с языками модуля
di(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

$loader = new Aura\Autoload\Loader();
$loader->register();
$loader->addPrefix('Albums', __DIR__ . '/lib');

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('album', __DIR__ . '/templates/');

$title = _t('Album');

// Добавляем раздел в навигационную цепочку
$nav_chain->add($title, '/album/');

$id = $request->getQuery('id', 0, FILTER_SANITIZE_NUMBER_INT);
$act = $request->getQuery('act', 'index');
$mod = $request->getQuery('mod', '');
$al = $request->getQuery('al', null, FILTER_SANITIZE_NUMBER_INT);
$img = $request->getQuery('img', null, FILTER_SANITIZE_NUMBER_INT);


$max_album = 20;
$max_photo = 400;

// Закрываем от неавторизованных юзеров
if (! $user->isValid()) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => _t('For registered users only'),
        ]
    );
    exit;
}

// Получаем данные пользователя
$foundUser = $tools->getUser(isset($_REQUEST['user']) ? abs((int) ($_REQUEST['user'])) : 0);
if (! $foundUser) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => _t('User does not exists'),
        ]
    );
    exit;
}

/**
 * Функция голосований за фотографии
 *
 * @param array $arg
 * @return bool|string
 */
function vote_photo(array $arg)
{
    global $db, $user;

    $rating = $arg['vote_plus'] - $arg['vote_minus'];

    if ($rating > 0) {
        $color = 'C0FFC0';
    } elseif ($rating < 0) {
        $color = 'F196A8';
    } else {
        $color = 'CCC';
    }

    $out = '<div class="gray">' . _t('Rating') . ': <span style="color:#000;background-color:#' . $color . '">&#160;&#160;<big><b>' . $rating . '</b></big>&#160;&#160;</span> ' .
        '(' . _t('Against') . ': ' . $arg['vote_minus'] . ', ' . _t('For') . ': ' . $arg['vote_plus'] . ')';

    if ($user->id != $arg['user_id'] && empty($user->ban) && $user->postforum > 10 && $user->total_on_site > 1200) {
        // Проверяем, имеет ли юзер право голоса
        $req = $db->query("SELECT * FROM `cms_album_votes` WHERE `user_id` = '" . $user->id . "' AND `file_id` = '" . $arg['id'] . "' LIMIT 1");

        if (! $req->rowCount()) {
            $out .= '<br>' . _t('Vote') . ': <a href="?act=vote&amp;mod=minus&amp;img=' . $arg['id'] . '">&lt;&lt; -1</a> | <a href="?act=vote&amp;mod=plus&amp;img=' . $arg['id'] . '">+1 &gt;&gt;</a>';
        }
    }
    $out .= '</div>';

    return $out;
}

$actions = [
    'comments',
    'delete',
    'edit',
    'image_delete',
    'image_download',
    'image_edit',
    'image_move',
    'image_upload',
    'list',
    'new_comm',
    'show',
    'sort',
    'top',
    'users',
    'vote',
    'index',
];

if (($key = array_search($act, $actions, true)) !== false) {
    require __DIR__ . '/includes/' . $actions[$key] . '.php';
} else {
    pageNotFound();
}
