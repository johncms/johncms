<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO                        $db
 * @var Johncms\Api\ToolsInterface $tools
 * @var Johncms\System\Users\User  $user
 */

$itmOnPage = $user->config->kmess;

if (! $al) {
    echo $tools->displayError(_t('Wrong data'));
    echo $view->render('system::app/old_content', [
        'title'   => $textl ?? '',
        'content' => ob_get_clean(),
    ]);
    exit;
}

$req = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '${al}'");

if (! $req->rowCount()) {
    echo $tools->displayError(_t('Wrong data'));
    echo $view->render('system::app/old_content', [
        'title'   => $textl ?? '',
        'content' => ob_get_clean(),
    ]);
    exit;
}

$album = $req->fetch();
$show = isset($_GET['view']);

// Показываем выбранный альбом с фотографиями
echo '<div class="phdr"><a href="./"><b>' . _t('Photo Albums') . '</b></a> | <a href="?act=list&amp;user=' . $foundUser['id'] . '">' . _t('Personal') . '</a></div>';

if ($foundUser['id'] == $user->id && empty($user->ban) || $user->rights >= 7) {
    echo '<div class="topmenu"><a href="?act=image_upload&amp;al=' . $al . '&amp;user=' . $foundUser['id'] . '">' . _t('Add image') . '</a></div>';
}

echo '<div class="user"><p>' . $tools->displayUser($foundUser) . '</p></div>' .
    '<div class="phdr">' . _t('Album') . ': ' .
    ($show ? '<a href="?act=show&amp;al=' . $al . '&amp;user=' . $foundUser['id'] . '"><b>' . $tools->checkout($album['name']) . '</b></a>' : '<b>' . $tools->checkout($album['name']) . '</b>');

if (! empty($album['description'])) {
    echo '<div class="sub">' . $tools->checkout($album['description'], 1) . '</div>';
}

echo '</div>';

// Проверяем права доступа к альбому
if ($album['access'] != 2) {
    unset($_SESSION['ap']);
}

if (($album['access'] == 1 || $album['access'] == 3)
    && $foundUser['id'] != $user->id
    && $user->rights < 7
) {
    // Доступ закрыт
    echo $tools->displayError(_t('Access forbidden'),
        '<a href="?act=list&amp;user=' . $foundUser['id'] . '">' . _t('Album List') . '</a>');
    echo $view->render('system::app/old_content', [
        'title'   => $textl ?? '',
        'content' => ob_get_clean(),
    ]);
    exit;
} elseif ($album['access'] == 2
    && $foundUser['id'] != $user->id
    && $user->rights < 7
) {
    // Доступ через пароль
    if (isset($_POST['password'])) {
        if ($album['password'] == trim($_POST['password'])) {
            $_SESSION['ap'] = $album['password'];
        } else {
            echo $tools->displayError(_t('Incorrect Password'));
        }
    }

    if (! isset($_SESSION['ap']) || $_SESSION['ap'] != $album['password']) {
        echo '<form action="?act=show&amp;al=' . $al . '&amp;user=' . $foundUser['id'] . '" method="post"><div class="menu"><p>' .
            _t('You must type a password to view this album') . '<br>' .
            '<input type="text" name="password"/></p>' .
            '<p><input type="submit" name="submit" value="' . _t('Login') . '"/></p>' .
            '</div></form>' .
            '<div class="phdr"><a href="?act=list&amp;user=' . $foundUser['id'] . '">' . _t('Album List') . '</a></div>';
        echo $view->render('system::app/old_content', [
            'title'   => $textl ?? '',
            'content' => ob_get_clean(),
        ]);
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

if ($total > $itmOnPage) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=show&amp;al=' . $al . '&amp;user=' . $foundUser['id'] . '&amp;' . ($show ? 'view&amp;' : ''),
            $start, $total, $itmOnPage) . '</div>';
}

if ($total) {
    $req = $db->query("SELECT * FROM `cms_album_files` WHERE `user_id` = '" . $foundUser['id'] . "' AND `album_id` = '${al}' ORDER BY `id` DESC LIMIT ${start}, ${itmOnPage}");
    $i = 0;

    while ($res = $req->fetch()) {
        echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
        if ($show) {
            // Устанавливаем выбранную картинку в свою анкету
            if ($foundUser['id'] == $user->id && isset($_GET['profile'])) {
                copy(
                    UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/' . $res['tmb_name'],
                    UPLOAD_PATH . 'users/photo/' . $user->id . '_small.jpg'
                );
                copy(
                    UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/' . $res['img_name'],
                    UPLOAD_PATH . 'users/photo/' . $user->id . '.jpg'
                );
                echo '<span class="green"><b>' . _t('Photo added to the profile') . '</b></span><br>';
            }

            // Предпросмотр отдельного изображения
            echo '<a href="' . $_SESSION['ref'] . '"><img src="../assets/modules/album/image.php?u=' . $foundUser['id'] . '&amp;f=' . $res['img_name'] . '" /></a>';

            // Счетчик просмотров
            if (! $db->query("SELECT COUNT(*) FROM `cms_album_views` WHERE `user_id` = '" . $user->id . "' AND `file_id` = " . $res['id'])->fetchColumn()) {
                $db->exec("INSERT INTO `cms_album_views` SET `user_id` = '" . $user->id . "', `file_id` = '" . $res['id'] . "', `time` = " . time());
                $views = $db->query("SELECT COUNT(*) FROM `cms_album_views` WHERE `file_id` = '" . $res['id'] . "'")->fetchColumn();
                $db->exec("UPDATE `cms_album_files` SET `views` = '${views}' WHERE `id` = " . $res['id']);
            }
        } else {
            // Предпросмотр изображения в списке
            echo '<a href="?act=show&amp;al=' . $al . '&amp;img=' . $res['id'] . '&amp;user=' . $foundUser['id'] . '&amp;view"><img src="../upload/users/album/' . $foundUser['id'] . '/' . $res['tmb_name'] . '" /></a>';
        }

        if (! empty($res['description'])) {
            echo '<div class="gray">' . $tools->smilies($tools->checkout($res['description'], 1)) . '</div>';
        }

        echo '<div class="sub">';

        if ($foundUser['id'] == $user->id || $user->rights >= 6) {
            echo implode(' | ', [
                '<a href="?act=image_edit&amp;img=' . $res['id'] . '&amp;user=' . $foundUser['id'] . '">' . _t('Edit') . '</a>',
                '<a href="?act=image_move&amp;img=' . $res['id'] . '&amp;user=' . $foundUser['id'] . '">' . _t('Move') . '</a>',
                '<a href="?act=image_delete&amp;img=' . $res['id'] . '&amp;user=' . $foundUser['id'] . '">' . _t('Delete') . '</a>',
            ]);

            if ($foundUser['id'] == $user->id && $show) {
                echo ' | <a href="?act=show&amp;al=' . $al . '&amp;user=' . $foundUser['id'] . '&amp;view&amp;img=' . $res['id'] . '&amp;profile">' . _t('Add to Profile') . '</a>';
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

if ($total > $itmOnPage) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=show&amp;al=' . $al . '&amp;user=' . $foundUser['id'] . '&amp;' . ($show ? 'view&amp;' : ''),
            $start, $total, $itmOnPage) . '</div>' .
        '<p><form action="?act=show&amp;al=' . $al . '&amp;user=' . $foundUser['id'] . ($show ? '&amp;view' : '') . '" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
        '</form></p>';
}

echo '<p><a href="?act=list&amp;user=' . $foundUser['id'] . '">' . _t('Album List') . '</a></p>';
