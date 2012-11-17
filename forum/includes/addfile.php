<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

require('../incfiles/head.php');
if (!$id || !$user_id) {
    echo functions::display_error($lng['error_wrong_data']);
    require('../incfiles/end.php');
    exit;
}

// Проверяем, тот ли юзер заливает файл и в нужное ли место
$req = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id'");
$res = mysql_fetch_assoc($req);
if ($res['type'] != 'm' || $res['user_id'] != $user_id) {
    echo functions::display_error($lng['error_wrong_data']);
    require('../incfiles/end.php');
    exit;
}

// Проверяем лимит времени, отведенный для выгрузки файла
if ($res['time'] < (time() - 180)) {
    echo functions::display_error($lng_forum['upload_timeout'], '<a href="index.php?id=' . $res['refid'] . '&amp;page=' . $page . '">' . $lng['back'] . '</a>');
    require('../incfiles/end.php');
    exit;
}

// Проверяем, был ли файл уже загружен
$exist = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_forum_files` WHERE `post` = '$id'"), 0);
if ($exist) {
    echo functions::display_error($lng_forum['error_file_uploaded']);
    require('../incfiles/end.php');
    exit;
}

if (isset($_POST['submit'])) {
    /*
    -----------------------------------------------------------------
    Проверка, был ли выгружен файл и с какого браузера
    -----------------------------------------------------------------
    */
    $do_file = false;
    $file = '';
    if ($_FILES['fail']['size'] > 0) {
        // Проверка загрузки с обычного браузера
        $do_file = true;
        $file = functions::rus_lat(mb_strtolower($_FILES['fail']['name']));
        $fsize = $_FILES['fail']['size'];
    }
    /*
    -----------------------------------------------------------------
    Обработка файла (если есть), проверка на ошибки
    -----------------------------------------------------------------
    */
    if ($do_file) {
        // Список допустимых расширений файлов.
        $al_ext = array_merge($ext_win, $ext_java, $ext_sis, $ext_doc, $ext_pic, $ext_arch, $ext_video, $ext_audio, $ext_other);
        $ext = explode(".", $file);
        $error = array();
        // Проверка на допустимый размер файла
        if ($fsize > 1024 * $set['flsz'])
            $error[] = $lng_forum['error_file_size'] . ' ' . $set['flsz'] . 'kb.';
        // Проверка файла на наличие только одного расширения
        if (count($ext) != 2)
            $error[] = $lng_forum['error_file_name'];
        // Проверка допустимых расширений файлов
        if (!in_array($ext[1], $al_ext))
            $error[] = $lng_forum['error_file_ext'] . ':<br />' . implode(', ', $al_ext);

        // Обработка названия файла
        if(mb_strlen($ext[0]) == 0){
            $ext[0] = '---';
        }
        $ext[0] = str_replace(" ", "_", $ext[0]);
        $fname = mb_substr($ext[0], 0, 32) . '.' . $ext[1];

        // Проверка на запрещенные символы
        if (preg_match("/[^\da-z_\-.]+/", $fname))
            $error[] = $lng_forum['error_file_symbols'];
        // Проверка наличия файла с таким же именем
        if (file_exists("../files/forum/attach/$fname")) {
            $fname = time() . $fname;
        }

        // Окончательная обработка
        if (!$error && $do_file) {
            // Для обычного браузера
            if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "../files/forum/attach/$fname")) == true) {
                @chmod("$fname", 0777);
                @chmod("../files/forum/attach/$fname", 0777);
                echo $lng_forum['file_uploaded'] . '<br/>';
            } else {
                $error[] = $lng_forum['error_upload_error'];
            }
        }

        if (!$error) {
            // Определяем тип файла
            $ext = strtolower($ext[1]);
            if (in_array($ext, $ext_win)) $type = 1;
            elseif (in_array($ext, $ext_java)) $type = 2; elseif (in_array($ext, $ext_sis)) $type = 3; elseif (in_array($ext, $ext_doc)) $type = 4; elseif (in_array($ext, $ext_pic)) $type = 5; elseif (in_array($ext, $ext_arch)) $type = 6; elseif (in_array($ext, $ext_video)) $type = 7; elseif (in_array($ext, $ext_audio)) $type = 8; else $type = 9;

            // Определяем ID субкатегории и категории
            $req2 = mysql_query("SELECT * FROM `forum` WHERE `id` = '" . $res['refid'] . "'");
            $res2 = mysql_fetch_array($req2);
            $req3 = mysql_query("SELECT * FROM `forum` WHERE `id` = '" . $res2['refid'] . "'");
            $res3 = mysql_fetch_array($req3);

            // Заносим данные в базу
            mysql_query("INSERT INTO `cms_forum_files` SET
                        `cat` = '" . $res3['refid'] . "',
                        `subcat` = '" . $res2['refid'] . "',
                        `topic` = '" . $res['refid'] . "',
                        `post` = '$id',
                        `time` = '" . $res['time'] . "',
                        `filename` = '" . mysql_real_escape_string($fname) . "',
                        `filetype` = '$type'
                    ");
        } else {
            echo functions::display_error($error, '<a href="index.php?act=addfile&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
        }
    } else {
        echo $lng_forum['error_upload_error'] . '<br />';
    }
    $pa = mysql_query("SELECT `id` FROM `forum` WHERE `type` = 'm' AND `refid` = '" . $res['refid'] . "'");
    $pa2 = mysql_num_rows($pa);
    $page = ceil($pa2 / $kmess);
    echo '<br/><a href="index.php?id=' . $res['refid'] . '&amp;page=' . $page . '">' . $lng['continue'] . '</a><br/>';
} else {
    /*
    -----------------------------------------------------------------
    Форма выбора файла для выгрузки
    -----------------------------------------------------------------
    */
    echo '<div class="phdr"><b>' . $lng_forum['add_file'] . '</b></div>' .
        '<div class="gmenu"><form action="index.php?act=addfile&amp;id=' . $id . '" method="post" enctype="multipart/form-data"><p>';
    if (stristr($agn, 'Opera/8.01')) {
        echo '<input name="fail1" value =""/>&#160;<br/><a href="op:fileselect">' . $lng_forum['select_file'] . '</a>';
    } else {
        echo '<input type="file" name="fail"/>';
    }
    echo '</p><p><input type="submit" name="submit" value="' . $lng_forum['upload'] . '"/></p></form></div>' .
        '<div class="phdr">' . $lng_forum['max_size'] . ': ' . $set['flsz'] . 'kb.</div>';
}