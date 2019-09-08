<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

require('../system/head.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if (!$al) {
    echo $tools->displayError(_t('Wrong data'));
    require('../system/end.php');
    exit;
}

$req = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '$al'");

if (!$req->rowCount()) {
    echo $tools->displayError(_t('Wrong data'));
    require('../system/end.php');
    exit;
}

$album = $req->fetch();
$view = isset($_GET['view']);

// Показываем выбранный альбом с фотографиями
echo '<div class="phdr"><a href="index.php"><b>' . _t('Photo Albums') . '</b></a> | <a href="?act=list&amp;user=' . $user['id'] . '">' . _t('Personal') . '</a></div>';

if ($user['id'] == $systemUser->id && empty($systemUser->ban) || $systemUser->rights >= 7) {
    echo '<div class="topmenu"><a href="?act=image_upload&amp;al=' . $al . '&amp;user=' . $user['id'] . '">' . _t('Add image') . '</a></div>';
}

echo '<div class="user"><p>' . $tools->displayUser($user) . '</p></div>' .
    '<div class="phdr">' . _t('Album') . ': ' .
    ($view ? '<a href="?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '"><b>' . $tools->checkout($album['name']) . '</b></a>' : '<b>' . $tools->checkout($album['name']) . '</b>');

if (!empty($album['description'])) {
    echo '<div class="sub">' . $tools->checkout($album['description'], 1) . '</div>';
}

echo '</div>';

// Проверяем права доступа к альбому
if ($album['access'] != 2) {
    unset($_SESSION['ap']);
}

if (($album['access'] == 1 || $album['access'] == 3)
    && $user['id'] != $systemUser->id
    && $systemUser->rights < 7
) {
    // Доступ закрыт
    echo $tools->displayError(_t('Access forbidden'), '<a href="?act=list&amp;user=' . $user['id'] . '">' . _t('Album List') . '</a>');
    require('../system/end.php');
    exit;
} elseif ($album['access'] == 2
    && $user['id'] != $systemUser->id
    && $systemUser->rights < 7
) {
    // Доступ через пароль
    if (isset($_POST['password'])) {
        if ($album['password'] == trim($_POST['password'])) {
            $_SESSION['ap'] = $album['password'];
        } else {
            echo $tools->displayError(_t('Incorrect Password'));
        }
    }

    if (!isset($_SESSION['ap']) || $_SESSION['ap'] != $album['password']) {
        echo '<form action="?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '" method="post"><div class="menu"><p>' .
            _t('You must type a password to view this album') . '<br>' .
            '<input type="text" name="password"/></p>' .
            '<p><input type="submit" name="submit" value="' . _t('Login') . '"/></p>' .
            '</div></form>' .
            '<div class="phdr"><a href="?act=list&amp;user=' . $user['id'] . '">' . _t('Album List') . '</a></div>';
        require('../system/end.php');
        exit;
    }
}

// Просмотр альбома и фотографий
if ($view) {
    $kmess = 1;
    $page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? intval($_REQUEST['page']) : 1;
    $start = isset($_REQUEST['page']) ? $page - 1 : ($db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `album_id` = '$al' AND `id` > '$img'")->fetchColumn());

    // Обрабатываем ссылку для возврата
    if (empty($_SESSION['ref'])) {
        $_SESSION['ref'] = htmlspecialchars($_SERVER['HTTP_REFERER']);
    }
} else {
    unset($_SESSION['ref']);
}

$total = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `album_id` = '$al'")->fetchColumn();

if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '&amp;' . ($view ? 'view&amp;' : ''), $start, $total, $kmess) . '</div>';
}

if ($total) {
    $req = $db->query("SELECT * FROM `cms_album_files` WHERE `user_id` = '" . $user['id'] . "' AND `album_id` = '$al' ORDER BY `id` DESC LIMIT $start, $kmess");
    $i = 0;

    while ($res = $req->fetch()) {
        echo($i % 2 ? '<div class="list2">' : '<div class="list1">');
        if ($view) {
            // Предпросмотр отдельного изображения
            if ($user['id'] == $systemUser->id && isset($_GET['profile'])) {
                copy(
                    '../files/users/album/' . $user['id'] . '/' . $res['tmb_name'],
                    '../files/users/photo/' . $systemUser->id . '_small.jpg'
                );
                copy(
                    '../files/users/album/' . $user['id'] . '/' . $res['img_name'],
                    '../files/users/photo/' . $systemUser->id . '.jpg'
                );
                echo '<span class="green"><b>' . _t('Photo added to the profile') . '</b></span><br>';
            }
            echo '<a href="' . $_SESSION['ref'] . '"><img src="image.php?u=' . $user['id'] . '&amp;f=' . $res['img_name'] . '" /></a>';

            // Счетчик просмотров
            if (!$db->query("SELECT COUNT(*) FROM `cms_album_views` WHERE `user_id` = '" . $systemUser->id . "' AND `file_id` = " . $res['id'])->fetchColumn()) {
                $db->exec("INSERT INTO `cms_album_views` SET `user_id` = '" . $systemUser->id . "', `file_id` = '" . $res['id'] . "', `time` = " . time());
                $views = $db->query("SELECT COUNT(*) FROM `cms_album_views` WHERE `file_id` = '" . $res['id'] . "'")->fetchColumn();
                $db->exec("UPDATE `cms_album_files` SET `views` = '$views' WHERE `id` = " . $res['id']);
            }
        } else {
            // Предпросмотр изображения в списке
            echo '<a href="?act=show&amp;al=' . $al . '&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '&amp;view"><img src="../files/users/album/' . $user['id'] . '/' . $res['tmb_name'] . '" /></a>';
        }

        if (!empty($res['description'])) {
            echo '<div class="gray">' . $tools->smilies($tools->checkout($res['description'], 1)) . '</div>';
        }

        echo '<div class="sub">';

        if ($user['id'] == $systemUser->id || $systemUser->rights >= 6) {
            echo implode(' | ', [
                '<a href="?act=image_edit&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . _t('Edit') . '</a>',
                '<a href="?act=image_move&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . _t('Move') . '</a>',
                '<a href="?act=image_delete&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . _t('Delete') . '</a>',
            ]);

            if ($user['id'] == $systemUser->id && $view) {
                echo ' | <a href="?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '&amp;view&amp;img=' . $res['id'] . '&amp;profile">' . _t('Add to Profile') . '</a>';
            }
        }

        echo vote_photo($res) .
            '<div class="gray">' . _t('Views') . ': ' . $res['views'] . ', ' . _t('Downloads') . ': ' . $res['downloads'] . '</div>' .
            '<div class="gray">' . _t('Date') . ': ' . $tools->displayDate($res['time']) . '</div>' .
            '<a href="?act=comments&amp;img=' . $res['id'] . '">' . _t('Comments') . '</a> (' . $res['comm_count'] . ')<br>' .
            '<a href="?act=image_download&amp;img=' . $res['id'] . '">' . _t('Download') . '</a>' .
            '</div></div>';
        ++$i;
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '&amp;' . ($view ? 'view&amp;' : ''), $start, $total, $kmess) . '</div>' .
        '<p><form action="?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . ($view ? '&amp;view' : '') . '" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
        '</form></p>';
}

echo '<p><a href="?act=list&amp;user=' . $user['id'] . '">' . _t('Album List') . '</a></p>';
