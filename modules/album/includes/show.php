<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Albums\Photo;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Utility\Tools $tools
 * @var Johncms\System\Users\User $user
 */

$itmOnPage = $user->config->kmess;

$data = [];

if (! $al) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => _t('Wrong data'),
        ]
    );
    exit;
}

$req = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '${al}'");

if (! $req->rowCount()) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => _t('Wrong data'),
        ]
    );
    exit;
}

$album = $req->fetch();
$show = isset($_GET['view']);
$data['album'] = $album;
$data['has_add_photo'] = (($foundUser['id'] === $user->id && empty($user->ban)) || $user->rights >= 7);
$nav_chain->add($tools->checkout($album['name']), '?act=show&amp;al=' . $al . '&amp;user=' . $foundUser['id']);

if ($show) {
    $nav_chain->add(_t('View photo'));
}

// Проверяем права доступа к альбому
if ($album['access'] !== 2) {
    unset($_SESSION['ap']);
}

if (
    ($album['access'] === 1 || $album['access'] === 3)
    && $foundUser['id'] !== $user->id
    && $user->rights < 7
) {
    // Доступ закрыт
    echo $view->render(
        'system::pages/result',
        [
            'title'         => $title,
            'type'          => 'alert-danger',
            'message'       => _t('Access denied'),
            'back_url'      => '?act=list&amp;user=' . $foundUser['id'],
            'back_url_name' => _t('Album List'),
        ]
    );
    exit;
}

if (
    $album['access'] === 2
    && $foundUser['id'] !== $user->id
    && $user->rights < 7
) {
    // Доступ через пароль
    if (isset($_POST['password'])) {
        if ($album['password'] === trim($_POST['password'])) {
            $_SESSION['ap'] = $album['password'];
        } else {
            echo $tools->displayError(_t('Incorrect Password'));
        }
    }

    if (! isset($_SESSION['ap']) || $_SESSION['ap'] !== $album['password']) {
        echo '<form action="?act=show&amp;al=' . $al . '&amp;user=' . $foundUser['id'] . '" method="post"><div class="menu"><p>' .
            _t('You must type a password to view this album') . '<br>' .
            '<input type="text" name="password"/></p>' .
            '<p><input type="submit" name="submit" value="' . _t('Login') . '"/></p>' .
            '</div></form>' .
            '<div class="phdr"><a href="?act=list&amp;user=' . $foundUser['id'] . '">' . _t('Album List') . '</a></div>';
        echo $view->render(
            'system::app/old_content',
            [
                'title'   => $textl ?? '',
                'content' => ob_get_clean(),
            ]
        );
        exit;
    }
}

// Просмотр альбома и фотографий
if ($show) {
    $itmOnPage = 1;
    $page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? (int) ($_REQUEST['page']) : 1;
    $start = isset($_REQUEST['page']) ? $page - 1 : ($db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `album_id` = '${al}' AND `id` > '${img}'")->fetchColumn());

    // Обрабатываем ссылку для возврата
    if (empty($_SESSION['ref'])) {
        $_SESSION['ref'] = htmlspecialchars($_SERVER['HTTP_REFERER']);
    }
} else {
    unset($_SESSION['ref']);
}

$total = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `album_id` = '${al}'")->fetchColumn();
if ($total) {
    $req = $db->query(
        "SELECT `files`.*, `users`.`name` AS `user_name`, `cms_album_cat`.`name` AS `album_name` FROM `cms_album_files` as files
        LEFT JOIN `users` ON `files`.`user_id` = `users`.`id`
        LEFT JOIN `cms_album_cat` ON `files`.`album_id` = `cms_album_cat`.`id`
        WHERE files.`user_id` = '" . $foundUser['id'] . "' AND files.`album_id` = '${al}' ORDER BY files.`id` DESC LIMIT ${start}, ${itmOnPage}"
    );

    $photos = [];
    while ($res = $req->fetch()) {
        $photos[] = new Photo($res);

        if ($show) {
            // Устанавливаем выбранную картинку в свою анкету
            if ($foundUser['id'] === $user->id && isset($_GET['profile'])) {
                copy(
                    UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/' . $res['tmb_name'],
                    UPLOAD_PATH . 'users/photo/' . $user->id . '_small.jpg'
                );
                copy(
                    UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/' . $res['img_name'],
                    UPLOAD_PATH . 'users/photo/' . $user->id . '.jpg'
                );
                $data['success_message'] = _t('Photo added to the profile');
            }

            // Счетчик просмотров
            if (! $db->query("SELECT COUNT(*) FROM `cms_album_views` WHERE `user_id` = '" . $user->id . "' AND `file_id` = " . $res['id'])->fetchColumn()) {
                $db->exec("INSERT INTO `cms_album_views` SET `user_id` = '" . $user->id . "', `file_id` = '" . $res['id'] . "', `time` = " . time());
                $views = $db->query("SELECT COUNT(*) FROM `cms_album_views` WHERE `file_id` = '" . $res['id'] . "'")->fetchColumn();
                $db->exec("UPDATE `cms_album_files` SET `views` = '${views}' WHERE `id` = " . $res['id']);
            }
        }
    }
}

$data['photos'] = $photos;
$data['total'] = $total;
$data['per_page'] = $itmOnPage;
$data['pagination'] = $tools->displayPagination('?act=show&amp;al=' . $al . '&amp;user=' . $foundUser['id'] . '&amp;' . ($show ? 'view&amp;' : ''), $start, $total, $itmOnPage);

$template = $show ? 'album::show_one' : 'album::show';
echo $view->render(
    $template,
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
