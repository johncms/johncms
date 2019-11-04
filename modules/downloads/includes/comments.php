<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\UserInterface $user */
$user = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

// Комментарии
if (! $config['mod_down_comm'] && $user->rights < 7) {
    echo _t('Comments are disabled') . ' <a href="?">' . _t('Downloads') . '</a>';
    exit;
}

/** @var PDO $db */
$db = $container->get(PDO::class);

$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (! $req_down->rowCount() || ! is_file($res_down['dir'] . '/' . $res_down['name']) || ($res_down['type'] == 3 && $user->rights < 6 && $user->rights != 4)) {
    echo _t('File not found') . ' <a href="?">' . _t('Downloads') . '</a>';
    echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
    exit;
}

if (! $config['mod_down_comm']) {
    echo '<div class="rmenu">' . _t('Comments are disabled') . '</div>';
}

$title_pages = htmlspecialchars(mb_substr($res_down['rus_name'], 0, 30));
$textl = _t('Comments') . ': ' . (mb_strlen($res_down['rus_name']) > 30 ? $title_pages . '...' : $title_pages);

// Параметры комментариев
$arg = [
    'object_comm_count' => 'total', // Поле с числом комментариев
    'comments_table'    => 'download__comments', // Таблица с комментариями
    'object_table'      => 'download__files', // Таблица комментируемых объектов
    'script'            => '?act=comments', // Имя скрипта (с параметрами вызова)
    'sub_id_name'       => 'id', // Имя идентификатора комментируемого объекта
    'sub_id'            => $id, // Идентификатор комментируемого объекта
    'owner'             => false, // Владелец объекта
    'owner_delete'      => false, // Возможность владельцу удалять комментарий
    'owner_reply'       => false, // Возможность владельцу отвечать на комментарий
    'owner_edit'        => false, // Возможность владельцу редактировать комментарий
    'title'             => _t('Comments'), // Название раздела
    'context_top'       => '<div class="phdr"><b>' . $textl . '</b></div>', // Выводится вверху списка
    'context_bottom'    => '<p><a href="?act=view&amp;id=' . $id . '">' . _t('Back') . '</a></p>', // Выводится внизу списка
];

// Показываем комментарии
$comm = new Johncms\Utility\Comments($arg);

echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
