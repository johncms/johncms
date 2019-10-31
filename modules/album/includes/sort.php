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

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

switch ($mod) {
    case 'up':
        // Передвигаем альбом на позицию вверх
        if ($al && $user['id'] == $systemUser->id || $systemUser->rights >= 7) {
            $req = $db->query("SELECT `sort` FROM `cms_album_cat` WHERE `id` = '${al}' AND `user_id` = " . $user['id']);
            if ($req->rowCount()) {
                $res = $req->fetch();
                $sort = $res['sort'];
                $req = $db->query("SELECT * FROM `cms_album_cat` WHERE `user_id` = '" . $user['id'] . "' AND `sort` < '${sort}' ORDER BY `sort` DESC LIMIT 1");
                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $id2 = $res['id'];
                    $sort2 = $res['sort'];
                    $db->exec("UPDATE `cms_album_cat` SET `sort` = '${sort2}' WHERE `id` = '${al}'");
                    $db->exec("UPDATE `cms_album_cat` SET `sort` = '${sort}' WHERE `id` = '${id2}'");
                }
            }
        }
        break;

    case 'down':
        // Передвигаем альбом на позицию вниз
        if ($al && $user['id'] == $systemUser->id || $systemUser->rights >= 7) {
            $req = $db->query("SELECT `sort` FROM `cms_album_cat` WHERE `id` = '${al}' AND `user_id` = " . $user['id']);
            if ($req->rowCount()) {
                $res = $req->fetch();
                $sort = $res['sort'];
                $req = $db->query("SELECT * FROM `cms_album_cat` WHERE `user_id` = '" . $user['id'] . "' AND `sort` > '${sort}' ORDER BY `sort` ASC LIMIT 1");
                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $id2 = $res['id'];
                    $sort2 = $res['sort'];
                    $db->query("UPDATE `cms_album_cat` SET `sort` = '${sort2}' WHERE `id` = '${al}'");
                    $db->query("UPDATE `cms_album_cat` SET `sort` = '${sort}' WHERE `id` = '${id2}'");
                }
            }
        }
        break;
}

header('Location: ?act=list&user=' . $user['id']);