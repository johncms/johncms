<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

define('_IN_JOHNCMS', 1);
$headmod = 'load';
require_once('../incfiles/core.php');
$lng_dl = core::load_lng('downloads');
require_once('../incfiles/lib/mp3.php');
require_once('../incfiles/lib/pclzip.lib.php');
$textl = $lng['downloads'];
$filesroot = '../download';
$screenroot = $filesroot . '/screen';
$loadroot = $filesroot . '/files';

// Ограничиваем доступ к Загрузкам
$error = '';
if (!$set['mod_down'] && $rights < 7) {
    $error = $lng_dl['downloads_closed'];
} elseif ($set['mod_down'] == 1 && !$user_id) {
    $error = $lng['access_guest_forbidden'];
}
if ($error) {
    require_once('../incfiles/head.php');
    echo '<div class="rmenu"><p>' . $error . '</p></div>';
    require_once("../incfiles/end.php");
    exit;
}

$cat = isset($_GET['cat']) ? abs(intval($_GET['cat'])) : 0;
$file = isset($_GET['file']) ? abs(intval($_GET['file'])) : 0;

$array = array (
    'addkomm',
    'arc',
    'delcat',
    'delmes',
    'dfile',
    'down',
    'import',
    'komm',
    'makdir',
    'new',
    'opis',
    'preview',
    'rat',
    'refresh',
    'ren',
    'screen',
    'search',
    'select',
    'upl',
    'view',
    'zip'
);
if (in_array($act, $array)) {
    require_once('includes/' . $act . '.php');
} else {
    require_once('../incfiles/head.php');
    if (!$set['mod_down']) {
        echo '<p><font color="#FF0000"><b>' . $lng_dl['downloads_closed'] . '</b></font></p>';
    }
    // Ссылка на новые файлы
    echo '<p><a href="?act=new">' . $lng['new_files'] . '</a> (' . $db->query("SELECT COUNT(*) FROM `download` WHERE `time` > '" . (time() - 259200) . "' AND `type` = 'file'")->fetchColumn() . ')</p>';
    $cat = isset($_GET['cat']) ? intval($_GET['cat']) : '';
    if (!$cat) {
        // Заголовок начальной страницы загрузок
        echo '<div class="phdr">' . $lng['downloads'] . '</div>';
    } else {
        // Заголовок страниц категорий
        $error = true;
        $stmt = $db->query("SELECT * FROM `download` WHERE `type` = 'cat' AND `id` = '" . $cat . "' LIMIT 1");
        if ($stmt->rowCount()) {
            $res = $stmt->fetch();
            if (is_dir($res['adres'] . '/' . $res['name'])) {
                $error = false;
            }
        }
        if ($error) {
            // Если неправильно выбран каталог, выводим ошибку
            echo functions::display_error($lng_dl['folder_does_not_exist'], '<a href="index.php">' . $lng['back'] . '</a>');
            require_once('../incfiles/end.php');
            exit;
        }
        ////////////////////////////////////////////////////////////
        // Получаем структуру каталогов                           //
        ////////////////////////////////////////////////////////////
        $tree = array ();
        $dirid = $cat;
        while ($dirid != '0' && $dirid != "") {
            $res = $db->query("SELECT * FROM `download` WHERE `type` = 'cat' and `id` = '" . $dirid . "' LIMIT 1")->fetch();
            $tree[] = '<a href="index.php?cat=' . $dirid . '">' . $res['text'] . '</a>';
            $dirid = $res['refid'];
        }
        krsort($tree);
        $cdir = array_pop($tree);
        echo '<div class="phdr"><a href="index.php"><b>' . $lng['downloads'] . '</b></a> | ';
        foreach ($tree as $value) {
            echo $value . ' | ';
        }
        echo strip_tags($cdir) . '</div>';
    }
    // Подсчитываем число папок
    $totalcat = $db->query("SELECT COUNT(*) FROM `download` WHERE `refid` = '$cat' AND `type` = 'cat'")->fetchColumn();
    // Подсчитываем число файлов
    $totalfile = $db->query("SELECT COUNT(*) FROM `download` WHERE `refid` = '$cat' AND `type` = 'file'")->fetchColumn();
    $total = $totalcat + $totalfile;
    if ($total > 0) {
        $stmt = $db->query("SELECT * FROM `download` WHERE `refid` = '$cat' ORDER BY `type` ASC, `text` ASC, `name` ASC LIMIT " . $start . "," . $kmess);
        while ($zap2 = $stmt->fetch()) {
            ////////////////////////////////////////////////////////////
            // Выводим список папок                                   //
            ////////////////////////////////////////////////////////////
            if ($totalcat > 0 && $zap2['type'] == 'cat') {
                echo '<div class="list1">';
                echo '<a href="?cat=' . $zap2['id'] . '">' . $zap2['text'] . '</a>';
                $g1 = 0;
                // Считаем число файлов в подкаталогах
                $g = $db->query("SELECT COUNT(*) FROM `download` WHERE `type` = 'file' AND `adres` LIKE '" . ($zap2['adres'] . '/' . $zap2['name']) . "%'")->fetchColumn();
                // Считаем новые файлы в подкаталогах
                $g1 = $db->query("SELECT COUNT(*) FROM `download` WHERE `type` = 'file' AND `adres` LIKE '" . ($zap2['adres'] . '/' . $zap2['name']) . "%' AND `time` > '" . (time() - 259200) . "'")->fetchColumn();
                echo "($g";
                if ($g1 != 0) {
                    echo "/+$g1)</div>";
                } else {
                    echo ")</div>";
                }
            }
            ////////////////////////////////////////////////////////////
            // Выводим cписок файлов                                  //
            ////////////////////////////////////////////////////////////
            if ($totalfile > 0 && $zap2['type'] == 'file') {
                echo '<div class="list2">';
                $ft = functions::format($zap2['name']);
                switch ($ft) {
                    case "mp3":
                        $imt = "mp3.png";
                        break;

                    case "zip":
                        $imt = "rar.png";
                        break;

                    case "jar":
                        $imt = "jar.png";
                        break;

                    case "gif":
                        $imt = "gif.png";
                        break;

                    case "jpg":
                        $imt = "jpg.png";
                        break;

                    case "png":
                        $imt = "png.png";
                        break;
                    default :
                        $imt = "file.gif";
                        break;
                }
                echo '<img src="' . $filesroot . '/img/' . $imt . '" alt=""/><a href="?act=view&amp;file=' . $zap2['id'] . '">' . htmlentities($zap2['name'], ENT_QUOTES, 'UTF-8') . '</a>';
                if ($zap2['text'] != "") {
                    // Выводим анонс текстового описания (если есть)
                    $tx = $zap2['text'];
                    if (mb_strlen($tx) > 100) {
                        $tx = mb_substr(strip_tags($tx), 0, 90);
                        $tx .= '...';
                    }
                    echo '<div class="sub">' . functions::checkout($tx) . '</div>';
                }
                echo '</div>';
            }
        }
    } else {
        echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
    }
    echo '<div class="phdr">';
    if ($totalcat > 0) {
        echo $lng_dl['folders'] . ': ' . $totalcat;
    }
    echo '&#160;&#160;';
    if ($totalfile > 0) {
        echo $lng_dl['files'] . ': ' . $totalfile;
    }
    echo '</div>';
    // Постраничная навигация
    if ($total > $kmess) {
        echo '<p>' . functions::display_pagination('index.php?cat=' . $cat . '&amp;', $start, $total, $kmess) . '</p>';
    }
    if ($rights == 4 || $rights >= 6) {
        ////////////////////////////////////////////////////////////
        // Выводим ссылки на модерские функции                    //
        ////////////////////////////////////////////////////////////
        echo '<p><div class="func">';
        echo '<a href="?act=makdir&amp;cat=' . $cat . '">' . $lng_dl['make_folder'] . '</a><br/>';
        if ($cat) {
            $delcat1 = $db->query("select COUNT(*) from `download` where type = 'cat' and refid = '" . $cat . "';")->fetchColumn();
            if (!$delcat1) {
                echo '<a href="index.php?act=delcat&amp;cat=' . $cat . '">' . $lng_dl['delete_folder'] . '</a><br />';
            }
            echo '<a href="index.php?act=ren&amp;cat=' . $cat . '">' . $lng_dl['rename_folder'] . '</a><br />';
            echo '<a href="index.php?act=select&amp;cat=' . $cat . '">' . $lng_dl['upload_file'] . '</a><br />';
            echo '<a href="index.php?act=import&amp;cat=' . $cat . '">' . $lng_dl['import_file'] . '</a><br />';
        }
        echo '<a href="index.php?act=refresh">' . $lng_dl['refresh_downloads'] . '</a>';
        echo '</div></p>';
    }
    if (!empty($cat))
        echo '<p><a href="index.php">' . $lng['downloads'] . '</a></p>';
    echo '<p><a href="index.php?act=preview">' . $lng_dl['images_size'] . '</a></p>';
    if (empty($cat) && $user_id) {
        echo '<form action="index.php?act=search" method="post">';
        echo $lng_dl['search_file'] . ': <br/><input type="text" name="srh" size="20" maxlength="20" value=""/><br/>';
        echo '<input type="submit" value="' . $lng['search'] . '"/></form>';
    }
}

require_once('../incfiles/end.php');