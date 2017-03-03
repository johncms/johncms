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

require '../system/head.php';

// Перенос файла
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name'])) {
    echo _t('File not found') . ' <a href="?">' . _t('Downloads') . '</a>';
    require '../system/end.php';
    exit;
}

$do = isset($_GET['do']) ? trim($_GET['do']) : '';

if ($systemUser->rights > 6) {
    $catId = isset($_GET['catId']) ? abs(intval($_GET['catId'])) : 0;

    if ($catId) {
        $queryDir = $db->query("SELECT * FROM `download__category` WHERE `id` = '$catId' LIMIT 1");

        if (!$queryDir->rowCount()) {
            $catId = 0;
        }
    }

    echo '<div class="phdr"><a href="?act=view&amp;id=' . $id . '">' . _t('Back') . '</a> | <b>' . _t('Move File') . '</b></div>';

    switch ($do) {
        case 'transfer':
            if ($catId) {
                if ($catId == $res_down['refid']) {
                    echo '<a href="?act=transfer_file&amp;id=' . $id . '&amp;catId=' . $catId . '">' . _t('Back') . '</a>';
                    require '../system/end.php';
                    exit;
                }

                if (isset($_GET['yes'])) {
                    $resDir = $queryDir->fetch();
                    $req_file_more = $db->query("SELECT * FROM `download__more` WHERE `refid` = '" . $id . "'");

                    if ($req_file_more->rowCount()) {
                        while ($res_file_more = $req_file_more->fetch()) {
                            copy($res_down['dir'] . '/' . $res_file_more['name'], $resDir['dir'] . '/' . $res_file_more['name']);
                            unlink($res_down['dir'] . '/' . $res_file_more['name']);
                        }
                    }

                    $name = $res_down['name'];
                    $newFile = $resDir['dir'] . '/' . $res_down['name'];

                    if (is_file($newFile)) {
                        $name = time() . '_' . $res_down['name'];
                        $newFile = $resDir['dir'] . '/' . $name;

                    }

                    copy($res_down['dir'] . '/' . $res_down['name'], $newFile);
                    unlink($res_down['dir'] . '/' . $res_down['name']);

                    $stmt = $db->prepare("
                        UPDATE `download__files` SET
                        `name`     = ?,
                        `dir`      = ?,
                        `refid`    = ?
                        WHERE `id` = ?
                    ");

                    $stmt->execute([
                        $name,
                        $resDir['dir'],
                        $catId,
                        $id,
                    ]);

                    echo '<div class="menu"><p>' . _t('The file has been moved') . '</p></div>' .
                        '<div class="phdr"><a href="?act=recount">' . _t('Update counters') . '</a></div>';
                } else {
                    echo '<div class="menu"><p><a href="?act=transfer_file&amp;id=' . $id . '&amp;catId=' . $catId . '&amp;do=transfer&amp;yes"><b>' . _t('Move File') . '</b></a></p></div>' .
                        '<div class="phdr"><br></div>';
                }
            }
            break;

        default:
            $queryCat = $db->query("SELECT * FROM `download__category` WHERE `refid` = '$catId'");
            $totalCat = $queryCat->rowCount();
            $i = 0;

            if ($totalCat > 0) {
                while ($resCat = $queryCat->fetch()) {
                    echo ($i++ % 2) ? '<div class="list2">' : '<div class="list1">';
                    echo '<a href="?act=transfer_file&amp;id=' . $id . '&amp;catId=' . $resCat['id'] . '">' . htmlspecialchars($resCat['rus_name']) . '</a>';

                    if ($resCat['id'] != $res_down['refid']) {
                        echo '<br><small><a href="?act=transfer_file&amp;id=' . $id . '&amp;catId=' . $resCat['id'] . '&amp;do=transfer">' . _t('Move to this folder') . '</a></small>';
                    }

                    echo '</div>';
                }
            } else {
                echo '<div class="rmenu"><p>' . _t('The list is empty') . '</p></div>';
            }

            echo '<div class="phdr">' . _t('Total') . ': ' . $totalCat . '</div>';

            if ($catId && $catId != $res_down['refid']) {
                echo '<p><div class="func"><a href="?act=transfer_file&amp;id=' . $id . '&amp;catId=' . $catId . '&amp;do=transfer">' . _t('Move to this folder') . '</a></div></p>';
            }
    }

    echo '<p><a href="?act=view&amp;id=' . $id . '">' . _t('Back') . '</a></p>';
}

require '../system/end.php';
