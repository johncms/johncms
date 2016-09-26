<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';

// Перенос файла
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name'])) {
    echo $lng['not_found_file'] . ' <a href="' . $url . '">' . _t('Downloads') . '</a>';
    exit;
}

$do = isset($_GET['do']) ? trim($_GET['do']) : '';

if ($rights > 6) {
    $catId = isset($_GET['catId']) ? abs(intval($_GET['catId'])) : 0;

    if ($catId) {
        $queryDir = $db->query("SELECT * FROM `download__category` WHERE `id` = '$catId' LIMIT 1");

        if (!$queryDir->rowCount()) {
            $catId = 0;
        }
    }

    echo '<div class="phdr"><a href="' . $url . '?act=view&amp;id=' . $id . '">' . _t('Back') . '</a> | <b>' . $lng['transfer_file'] . '</b></div>';

    switch ($do) {
        case 'transfer':
            if ($catId) {
                if ($catId == $res_down['refid']) {
                    echo '<a href="' . $url . '?act=transfer_file&amp;id=' . $id . '&amp;catId=' . $catId . '">' . _t('Back') . '</a>';
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

                    echo '<div class="menu"><p>' . $lng['transfer_file_ok'] . '</p></div>' .
                        '<div class="phdr"><a href="' . $url . '?act=recount">' . $lng['download_recount'] . '</a></div>';
                } else {
                    echo '<div class="menu"><p><a href="' . $url . '?act=transfer_file&amp;id=' . $id . '&amp;catId=' . $catId . '&amp;do=transfer&amp;yes"><b>' . $lng['transfer_file'] . '</b></a></p></div>' .
                        '<div class="phdr"><br /></div>';
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
                    echo Functions::loadModuleImage('folder.png') . '&#160;' .
                        '<a href="' . $url . '?act=transfer_file&amp;id=' . $id . '&amp;catId=' . $resCat['id'] . '">' . htmlspecialchars($resCat['rus_name']) . '</a>';

                    if ($resCat['id'] != $res_down['refid']) {
                        echo '<br /><small><a href="' . $url . '?act=transfer_file&amp;id=' . $id . '&amp;catId=' . $resCat['id'] . '&amp;do=transfer">' . $lng['move_this_folder'] . '</a></small>';
                    }

                    echo '</div>';
                }
            } else {
                echo '<div class="rmenu"><p>' . $lng['list_empty'] . '</p></div>';
            }

            echo '<div class="phdr">' . $lng['total'] . ': ' . $totalCat . '</div>';

            if ($catId && $catId != $res_down['refid']) {
                echo '<p><div class="func"><a href="' . $url . '?act=transfer_file&amp;id=' . $id . '&amp;catId=' . $catId . '&amp;do=transfer">' . $lng['move_this_folder'] . '</a></div></p>';
            }
    }

    echo '<p><a href="' . $url . '?act=view&amp;id=' . $id . '">' . _t('Back') . '</a></p>';
} else {
    header('Location: ' . App::cfg()->sys->homeurl . '404');
}
