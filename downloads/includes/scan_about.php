<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

// Обновление описаний
if ($rights == 4 || $rights >= 6) {
    set_time_limit(99999);
    $dir = glob($down_path . '/about/*.txt');

    foreach ($dir as $val) {
        if (isset($_GET['clean'])) {
            @unlink($val);
        } else {
            $file_id = abs(intval(preg_replace('#' . $down_path . '/about/([0-9]+)\.txt#si', '\1', $val, 1)));

            if ($file_id) {
                /** @var PDO $db */
                $db = App::getContainer()->get(PDO::class);

                $stmt = $db->prepare("
                    UPDATE `download__files`
                    SET `about` = ?
                    WHERE `id` = ?
                ");

                $stmt->execute([file_get_contents($val), $file_id]);
            }
        }
    }

    require '../incfiles/head.php';
    echo '<div class="phdr"><b>' . _t('Update descriptions') . '</b></div>';

    if (isset($_GET['clean'])) {
        echo '<div class="rmenu"><p>' . _t('Folder cleared') . '</p></div>';
    } else {
        echo '<div class="gmenu"><p>' . _t('Descriptions updated') . '</p></div>' .
            '<div class="rmenu"><a href="?act=scan_about&amp;clean&amp;id=' . $id . '">' . _t('Empty Folder') . '</a></div>';
    }

    echo '<div class="phdr"><a href="?id=' . $id . '">' . _t('Back') . '</a></div>';
    require '../incfiles/end.php';
}
