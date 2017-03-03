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

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Проверяем наличие комментируемого объекта
$req_obj = $db->query("SELECT * FROM `cms_album_files` WHERE `id` = '$img'");

if ($req_obj->rowCount()) {
    $res_obj = $req_obj->fetch();

    // Получаем данные владельца Альбома
    $owner = $tools->getUser($res_obj['user_id']);

    if (!$owner) {
        require('../system/head.php');
        echo $tools->displayError(_t('User does not exists'));
        require('../system/end.php');
        exit;
    }

    // Показываем выбранную картинку
    unset($_SESSION['ref']);
    $res_a = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = " . $res_obj['album_id'])->fetch();

    if (($res_a['access'] == 1 && $owner['id'] != $systemUser->id && $systemUser->rights < 7) || ($res_a['access'] == 2 && $systemUser->rights < 7 && (!isset($_SESSION['ap']) || $_SESSION['ap'] != $res_a['password']) && $owner['id'] != $systemUser->id)) {
        // Если доступ закрыт
        require('../system/head.php');
        echo $tools->displayError(_t('Access forbidden')) .
            '<div class="phdr"><a href="?act=list&amp;user=' . $owner['id'] . '">' . _t('Album List') . '</a></div>';
        require('../system/end.php');
        exit;
    }

    $context_top = '<div class="phdr"><a href="index.php"><b>' . _t('Photo Albums') . '</b></a> | ' .
        '<a href="?act=list&amp;user=' . $owner['id'] . '">' . _t('Personal') . '</a></div>' .
        '<div class="menu"><a href="?act=show&amp;al=' . $res_obj['album_id'] . '&amp;img=' . $img . '&amp;user=' . $owner['id'] . '&amp;view"><img src="../files/users/album/' . $owner['id'] . '/' . $res_obj['tmb_name'] . '" /></a>';

    if (!empty($res_obj['description'])) {
        $context_top .= '<div class="gray">' . $tools->smilies($tools->checkout($res_obj['description'], 1)) . '</div>';
    }

    $context_top .= '<div class="sub">' .
        '<a href="../profile/?user=' . $owner['id'] . '"><b>' . $owner['name'] . '</b></a> | ' .
        '<a href="?act=show&amp;al=' . $res_a['id'] . '&amp;user=' . $owner['id'] . '">' . $tools->checkout($res_a['name']) . '</a>';

    if ($res_obj['access'] == 4 || $systemUser->rights >= 7) {
        $context_top .= vote_photo($res_obj) .
            '<div class="gray">' . _t('Views') . ': ' . $res_obj['views'] . ', ' . _t('Downloads') . ': ' . $res_obj['downloads'] . '</div>' .
            '<a href="?act=image_download&amp;img=' . $res_obj['id'] . '">' . _t('Download') . '</a>';
    }

    $context_top .= '</div></div>';

    // Параметры комментариев
    $arg = [
        'comments_table' => 'cms_album_comments',     // Таблица с комментариями
        'object_table'   => 'cms_album_files',        // Таблица комментируемых объектов
        'script'         => '?act=comments', // Имя скрипта (с параметрами вызова)
        'sub_id_name'    => 'img',                    // Имя идентификатора комментируемого объекта
        'sub_id'         => $img,                     // Идентификатор комментируемого объекта
        'owner'          => $owner['id'],             // Владелец объекта
        'owner_delete'   => true,                     // Возможность владельцу удалять комментарий
        'owner_reply'    => true,                     // Возможность владельцу отвечать на комментарий
        'owner_edit'     => false,                    // Возможность владельцу редактировать комментарий
        'title'          => _t('Comments'),         // Название раздела
        'context_top'    => $context_top,             // Выводится вверху списка
        'context_bottom' => ''                        // Выводится внизу списка
    ];

    // Ставим метку прочтения
    if ($systemUser->id == $owner['id'] && $res_obj['unread_comments']) {
        $db->exec("UPDATE `cms_album_files` SET `unread_comments` = '0' WHERE `id` = '$img' LIMIT 1");
    }

    // Показываем комментарии
    require('../system/head.php');
    $comm = new Johncms\Comments($arg);

    // Обрабатываем метки непрочитанных комментариев
    if ($comm->added && $systemUser->id != $owner['id']) {
        $db->exec("UPDATE `cms_album_files` SET `unread_comments` = '1' WHERE `id` = '$img' LIMIT 1");
    }
} else {
    require('../system/head.php');
    echo $tools->displayError(_t('Wrong data'));
}
