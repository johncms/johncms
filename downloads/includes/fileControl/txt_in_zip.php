<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';

// Скачка TXT файла в ZIP
$dir_clean = opendir(ROOT_PATH . 'files/download/temp/created_zip');

while ($file = readdir($dir_clean)) {
    if ($file != 'index.php' && $file != '.htaccess' && $file != '.' && $file != '..') {
        $time_file = filemtime(ROOT_PATH . 'files/download/temp/created_zip/' . $file);

        if ($time_file < (time() - 300)) {
            @unlink(ROOT_PATH . 'files/download/temp/created_zip/' . $file);
        }
    }
}

closedir($dir_clean);
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name']) || (functions::format($res_down['name']) != 'txt' && !isset($_GET['more'])) || ($res_down['type'] == 3 && $rights < 6 && $rights != 4)) {
    echo $lng['not_found_file'] . '<a href="' . $url . '">' . _t('Downloads') . '</a>';
    exit;
}

if (isset($_GET['more'])) {
    $more = abs(intval($_GET['more']));
    $req_more = $db->query("SELECT * FROM `download__more` WHERE `id` = '$more' LIMIT 1");
    $res_more = $req_more->fetch();

    if (!$req_more->rowCount() || !is_file($res_down['dir'] . '/' . $res_more['name']) || functions::format($res_more['name']) != 'txt') {
        echo $lng['not_found_file'] . ' <a href="' . $url . '">' . _t('Downloads') . '</a>';
        exit;
    }

    $down_file = $res_down['dir'] . '/' . $res_more['name'];
    $title_pages = $res_more['rus_name'];
    $txt_file = $res_more['name'];
} else {
    $down_file = $res_down['dir'] . '/' . $res_down['name'];
    $title_pages = $res_down['rus_name'];
    $txt_file = $res_down['name'];
}

if (!isset($_SESSION['down_' . $id])) {
    $db->exec("UPDATE `download__files` SET `field`=`field`+1 WHERE `id`=" . $id);
    $_SESSION['down_' . $id] = 1;
}

$file = 'files/download/temp/created_zip/' . $txt_file . '.zip';

if (!file_exists($file)) {
    require(SYSPATH . 'lib/pclzip.lib.php');
    $zip = new PclZip($file);

    function w($event, &$header)
    {
        $header['stored_filename'] = basename($header['filename']);

        return 1;
    }

    $zip->create($down_file, PCLZIP_CB_PRE_ADD, 'w');
    chmod($file, 0644);
}

// Ссылка на файл
echo '<div class="phdr"><b>' . htmlspecialchars($title_pages) . '</b></div>' .
    '<div class="menu"><a href="' . htmlspecialchars($file) . '">' . $lng['download_in'] . ' ZIP</a></div>' .
    '<div class="rmenu"><input type="text" value="' . App::cfg()->sys->homeurl . htmlspecialchars($file) . '"/><b></b></div>' .
    '<div class="phdr">' . $lng['time_limit'] . '</div>' .
    '<p><a href="' . $url . '?act=view&amp;id=' . $id . '">' . $lng['back'] . '</a></p>';
