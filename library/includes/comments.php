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

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

if (!$systemUser->isValid()) {
    echo $tools->displayError(_t('Access forbidden'));
    require_once('../system/end.php');
    exit;
}

// Проверяем наличие комментируемого объекта
$req_obj = $db->query("SELECT * FROM `library_texts` WHERE `id`=" . $id);

if ($req_obj->rowCount()) {
    $res_obj = $req_obj->fetch();

    if (!$res_obj) {
        echo $tools->displayError(_t('Access forbidden'));
        require('../system/end.php');
        exit;
    }

    $obj = new Library\Hashtags($id);
    $catalog = $db->query("SELECT `id`, `name` FROM `library_cats` WHERE `id`=" . $res_obj['cat_id'] . " LIMIT 1")->fetch();
    $context_top =
        '<div class="phdr"><a href="?"><strong>' . _t('Library') . '</strong></a> | <a href="?do=dir&amp;id=' . $catalog['id'] . '">' . $tools->checkout($catalog['name']) . '</a></div>' .
        '<div class="menu">' .
        '<p><b><a href="index.php?id=' . $id . '">' . $tools->checkout($res_obj['name']) . '</a></b></p>' .
        '<small>' . $tools->smilies($tools->checkout($res_obj['announce'], 1, 1)) . '</small>' .
        '<div class="sub">' .
        ($obj->getAllStatTags() ? '<span class="gray">' . _t('Tags') . ':</span> [ ' . $obj->getAllStatTags(1) . ' ]<br>' : '') .
        '<span class="gray">' . _t('Who added') . ':</span> <a href="' . $config['homeurl'] . '/profile/?user=' . $res_obj['uploader_id'] . '">' . $tools->checkout($res_obj['uploader']) . '</a> (' . $tools->displayDate($res_obj['time']) . ')<br>' .
        '<span class="gray">' . _t('Number of readings') . ':</span> ' . $res_obj['count_views'] .
        '</div></div>';
    $arg = [
        'comments_table' => 'cms_library_comments',
        // Таблица с комментариями
        'object_table' => 'library_texts',
        // Таблица комментируемых объектов
        'script' => '?act=comments',
        // Имя скрипта (с параметрами вызова)
        'sub_id_name' => 'id',
        // Имя идентификатора комментируемого объекта
        'sub_id' => $id,
        // Идентификатор комментируемого объекта
        'owner' => $res_obj['uploader_id'],
        // Владелец объекта (ID того юзера, который может управлять каментами, если разрешено ниже)
        'owner_delete' => true,
        // Возможность владельцу удалять комментарий
        'owner_reply' => true,
        // Возможность владельцу отвечать на комментарий
        'owner_edit' => false,
        // Возможность владельцу редактировать комментарий
        'title' => _t('Comments'),
        // Название раздела
        'context_top' => $context_top,
        // Выводится вверху списка
    ];
    $comm = new Johncms\Comments($arg);

    if ($comm->added) {
        $db->exec("UPDATE `library_texts` SET `comm_count`=" . ($res_obj['comm_count'] > 0 ? ++$res_obj['comm_count'] : 1) . " WHERE `id`=" . $id);
    }
} else {
    echo $tools->displayError(_t('Wrong data'));
}
