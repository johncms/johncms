<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

// Скачка TXT файла в JAR
$dir_clean = opendir(ROOT_PATH . 'files/download/temp/created_java/files');

while ($file = readdir($dir_clean)) {
    if ($file != 'index.php' && $file != '.htaccess' && $file != '.' && $file != '..') {
        $time_file = filemtime(ROOT_PATH . 'files/download/temp/created_java/files/' . $file);

        if ($time_file < (time() - 300)) {
            @unlink(ROOT_PATH . 'files/download/temp/created_java/files/' . $file);
        }
    }
}

closedir($dir_clean);
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();
$format_file = functions::format($res_down['name']);

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name']) || ($format_file != 'txt' && !isset($_GET['more'])) || ($res_down['type'] == 3 && $rights < 6 && $rights != 4)) {
    echo _t('File not found') . '<a href="?">' . _t('Downloads') . '</a>';
    exit;
}

if (isset($_GET['more'])) {
    $more = abs(intval($_GET['more']));
    $req_more = $db->query("SELECT * FROM `download__more` WHERE `id` = '$more' LIMIT 1");
    $res_more = $req_more->fetch();
    $format_file = functions::format($res_more['name']);

    if (!$req_more->rowCount() || !is_file($res_down['dir'] . '/' . $res_more['name']) || $format_file != 'txt') {
        echo _t('File not found') . '<a href="?">' . _t('Downloads') . '</a>';
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

$file = str_replace('.' . $format_file, '', $txt_file);
$name = str_replace('.' . $format_file, '', $txt_file);
$tmp = 'files/download/temp/created_java/files/' . $name . '.jar';
$tmp_jad = 'files/download/temp/created_java/files/' . $name . '.jar.jad';

if (!file_exists($tmp)) {
    $midlet_name = mb_substr($res_down['rus_name'], 0, 10);
    $midlet_name = iconv('UTF-8', 'windows-1251', $midlet_name);
    $book_text = file_get_contents($res_down['dir'] . '/' . $res_down['name']);
    $charset_text = strtolower(mb_detect_encoding($book_text, 'UTF-8, windows-1251'));

    if ($charset_text != 'windows-1251') {
        @$book_text = iconv('utf-8', 'windows-1251', $book_text);
    }

    $files = fopen("files/download/temp/created_java/java/textfile.txt", 'w+');
    flock($files, LOCK_EX);
    $book_name = iconv('UTF-8', 'windows-1251', $res_down['rus_name']);
    $result = "\r\n" . $book_name . "\r\n\r\n----------\r\n\r\n" . trim($book_text) . "\r\n\r\nDownloaded from " . $set['homeurl'];
    fputs($files, $result);
    flock($files, LOCK_UN);
    fclose($files);
    $manifest_text = 'Manifest-Version: 1.0
MIDlet-1: Файл #' . $id . ', , br.BookReader
MIDlet-Name: $tmp_jad
MIDlet-Vendor: mobiCMS
MIDlet-Version: 1.5.3
MIDletX-No-Command: true
MIDletX-LG-Contents: true
MicroEdition-Configuration: CLDC-1.0
MicroEdition-Profile: MIDP-1.0
TCBR-Platform: Generic version (all phones)';
    $files = fopen("files/download/temp/created_java/java/META-INF/MANIFEST.MF", 'w+');
    flock($files, LOCK_EX);
    fputs($files, $manifest_text);
    flock($files, LOCK_UN);
    fclose($files);
    require(SYSPATH . 'lib/pclzip.lib.php');
    $archive = new PclZip($tmp);
    $list = $archive->create('files/download/temp/created_java/java', PCLZIP_OPT_REMOVE_PATH, 'files/download/temp/created_java/java');

    if (!file_exists($tmp)) {
        echo _t('Error creating the JAR file');
        exit;
    }
}

if (!file_exists($tmp_jad)) {
    $filesize = filesize($tmp);
    $jad_text = 'Manifest-Version: 1.0
MIDlet-1: Файл #' . $id . ', , br.BookReader
MIDlet-Name: Файл #' . $id . '
MIDlet-Vendor: mobiCMS
MIDlet-Version: 1.5.3
MIDletX-No-Command: true
MIDletX-LG-Contents: true
MicroEdition-Configuration: CLDC-1.0
MicroEdition-Profile: MIDP-1.0
TCBR-Platform: Generic version (all phones)
MIDlet-Jar-Size: ' . $filesize . '
MIDlet-Jar-URL: ' . $set['homeurl'] . '/' . $tmp; //TODO: Переделать ссылку
    $files = fopen($tmp_jad, 'w+');
    flock($files, LOCK_EX);
    fputs($files, $jad_text);
    flock($files, LOCK_UN);
    fclose($files);
}

// Ссылки на файлы
echo '<div class="phdr"><b>' . htmlspecialchars($title_pages) . '</b></div>' .
    '<div class="menu">' . _t('Download') . ': <a href="' . htmlspecialchars($tmp) . '">JAR</a> | <a href="' . htmlspecialchars($tmp_jad) . '">JAD</a></div>' .
    '<div class="phdr">' . _t('The file will be available for download within 5 minutes') . '</div>' .
    '<p><a href="?act=view&amp;id=' . $id . '">' . _t('Back') . '</a></p>';
