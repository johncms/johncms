<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

// Обновление описаний
if ($rights == 4 || $rights >= 6) {
    set_time_limit(99999);
    $dir = glob($down_path . '/about/*.txt');
    $lng = core::load_lng('dl');

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

    echo '<div class="phdr"><b>' . $lng['download_scan_about'] . '</b></div>';

    if (isset($_GET['clean'])) {
        echo '<div class="rmenu"><p>' . $lng['scan_about_clean_ok'] . '</p></div>';
    } else {
        echo '<div class="gmenu"><p>' . $lng['scan_about_ok'] . '</p></div>' .
            '<div class="rmenu"><a href="?act=scan_about&amp;clean&amp;id=' . App::request()->getQuery('id', '') . '">' . $lng['scan_about_clean'] . '</a></div>';
    }
    echo '<div class="phdr"><a href="?id=' . App::request()->getQuery('id', '') . '">' . $lng['back'] . '</a></div>';
} else {
    header('Location: ' . App::cfg()->sys->homeurl . '404');
}
