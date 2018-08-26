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

$headmod = 'forumfiles';
require('../incfiles/head.php');

$types = array(
    1 => $lng_forum['files_type_win'],
    2 => $lng_forum['files_type_java'],
    3 => $lng_forum['files_type_sis'],
    4 => $lng_forum['files_type_txt'],
    5 => $lng_forum['files_type_pic'],
    6 => $lng_forum['files_type_arc'],
    7 => $lng_forum['files_type_video'],
    8 => $lng_forum['files_type_audio'],
    9 => $lng_forum['files_type_other']
);
$new = time() - 86400; // Сколько времени файлы считать новыми?

/*
-----------------------------------------------------------------
Получаем ID раздела и подготавливаем запрос
-----------------------------------------------------------------
*/
$c = isset($_GET['c']) ? abs(intval($_GET['c'])) : false; // ID раздела
$s = isset($_GET['s']) ? abs(intval($_GET['s'])) : false; // ID подраздела
$t = isset($_GET['t']) ? abs(intval($_GET['t'])) : false; // ID топика
$do = isset($_GET['do']) && intval($_GET['do']) > 0 && intval($_GET['do']) < 10 ? intval($_GET['do']) : 0;
if ($c) {
    $id = $c;
    $lnk = '&amp;c=' . $c;
    $sql = " AND `cat` = '" . $c . "'";
    $caption = '<b>' . $lng_forum['files_category'] . '</b>: ';
    $input = '<input type="hidden" name="c" value="' . $c . '"/>';
} elseif ($s) {
    $id = $s;
    $lnk = '&amp;s=' . $s;
    $sql = " AND `subcat` = '" . $s . "'";
    $caption = '<b>' . $lng_forum['files_section'] . '</b>: ';
    $input = '<input type="hidden" name="s" value="' . $s . '"/>';
} elseif ($t) {
    $id = $t;
    $lnk = '&amp;t=' . $t;
    $sql = " AND `topic` = '" . $t . "'";
    $caption = '<b>' . $lng_forum['files_topic'] . '</b>: ';
    $input = '<input type="hidden" name="t" value="' . $t . '"/>';
} else {
    $id = false;
    $sql = '';
    $lnk = '';
    $caption = '<b>' . $lng_forum['files_forum'] . '</b>';
    $input = '';
}
if ($c || $s || $t) {
    // Получаем имя нужной категории форума
    $stmt = $db->query("SELECT `text` FROM `forum` WHERE `id` = '$id' LIMIT 1");
    if ($stmt->rowCount()) {
        $res = $stmt->fetch();
        $caption .= _e($res['text']);
    } else {
        echo functions::display_error($lng['error_wrong_data'], '<a href="index.php">' . $lng['to_forum'] . '</a>');
        require('../incfiles/end.php');
        exit;
    }
}
if ($do || isset($_GET['new'])) {
    /*
    -----------------------------------------------------------------
    Выводим список файлов нужного раздела
    -----------------------------------------------------------------
    */
    $total = $db->query("SELECT COUNT(*) FROM `cms_forum_files` WHERE " . (isset($_GET['new'])
                                              ? " `time` > '$new'" : " `filetype` = '$do'") . $sql)->fetchColumn();
    if ($total > 0) {
        // Заголовок раздела
        echo '<div class="phdr">' . $caption . (isset($_GET['new']) ? '<br />' . $lng['new_files']
                : '') . '</div>' . ($do ? '<div class="bmenu">' . $types[$do] . '</div>' : '');
        $stmt = $db->query("SELECT `cms_forum_files`.*, `forum`.`user_id`, `forum`.`text`, `topicname`.`text` AS `topicname`
            FROM `cms_forum_files`
            LEFT JOIN `forum` ON `cms_forum_files`.`post` = `forum`.`id`
            LEFT JOIN `forum` AS `topicname` ON `cms_forum_files`.`topic` = `topicname`.`id`
            WHERE " . (isset($_GET['new']) ? " `cms_forum_files`.`time` > '$new'" : " `filetype` = '$do'") . ($rights >= 7 ? '' : " AND `del` != '1'") . $sql .
            "ORDER BY `time` DESC LIMIT $start,$kmess");
        $i = 0;
        while ($res = $stmt->fetch()) {
            $res_u = $db->query("SELECT `id`, `name`, `sex`, `rights`, `lastdate`, `status`, `datereg`, `ip`, `browser` FROM `users` WHERE `id` = '" . $res['user_id'] . "' LIMIT 1")->fetch();
            echo ++$i % 2 ? '<div class="list2">' : '<div class="list1">';
            // Выводим текст поста
            $text = mb_substr($res['text'], 0, 500);
            $text = functions::checkout($text, 1, 0);
            $text = preg_replace('#\[c\](.*?)\[/c\]#si', '', $text);
            $page = ceil($db->query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['topic'] . "' AND `id` " . ($set_forum['upfp']
                                                          ? ">=" : "<=") . " '" . $res['post'] . "'")->fetchColumn() / $kmess);
            $text = '<b><a href="index.php?id=' . $res['topic'] . '&amp;page=' . $page . '">' . _e($res['topicname']) . '</a></b><br />' . $text;
            if (mb_strlen($res['text']) > 500) {
                $text .= '<br /><a href="index.php?act=post&amp;id=' . $res['post'] . '">' . $lng_forum['read_all'] . ' &gt;&gt;</a>';
            }
            // Формируем ссылку на файл
            $fls = @filesize('../files/forum/attach/' . $res['filename']);
            $fls = round($fls / 1024, 0);
            $att_ext = strtolower(functions::format('./files/forum/attach/' . $res['filename']));
            $pic_ext = array(
                'gif',
                'jpg',
                'jpeg',
                'png'
            );
            if (in_array($att_ext, $pic_ext)) {
                // Если картинка, то выводим предпросмотр
                $file = '<div><a href="index.php?act=file&amp;id=' . $res['id'] . '">';
                $file .= '<img src="thumbinal.php?file=' . (urlencode($res['filename'])) . '" alt="' . $lng_forum['click_to_view'] . '" /></a></div>';
            } else {
                // Если обычный файл, выводим значок и ссылку
                $file = ($res['del'] ? '<img src="../images/del.png" width="16" height="16" />'
                        : '') . '<img src="../images/system/' . $res['filetype'] . '.png" width="16" height="16" />&#160;';
            }
            $file .= '<a href="index.php?act=file&amp;id=' . $res['id'] . '">' . htmlspecialchars($res['filename']) . '</a><br />';
            $file .= '<small><span class="gray">' . $lng_forum['size'] . ': ' . $fls . ' kb.<br />' . $lng_forum['downloaded'] . ': ' . $res['dlcount'] . ' ' . $lng_forum['time'] . '</span></small>';
            $arg = array(
                'iphide' => 1,
                'sub' => $file,
                'body' => $text
            );
            echo functions::display_user($res_u, $arg);
            echo '</div>';
        }
        echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
        if ($total > $kmess) {
            // Постраничная навигация
            echo '<p>' . functions::display_pagination('index.php?act=files&amp;' . (isset($_GET['new']) ? 'new'
                                       : 'do=' . $do) . $lnk . '&amp;', $start, $total, $kmess) . '</p>' .
                 '<p><form action="index.php" method="get">' .
                 '<input type="hidden" name="act" value="files"/>' .
                 '<input type="hidden" name="do" value="' . $do . '"/>' . $input . '<input type="text" name="page" size="2"/>' .
                 '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
        }
    } else {
        echo '<div class="list1">' . $lng['list_empty'] . '</div>';
    }
} else {
    /*
    -----------------------------------------------------------------
    Выводим список разделов, в которых есть файлы
    -----------------------------------------------------------------
    */
    $countnew = $db->query("SELECT COUNT(*) FROM `cms_forum_files` WHERE `time` > '$new'" . ($rights >= 7
                                                 ? '' : " AND `del` != '1'") . $sql)->fetchColumn();
    echo '<p>' . ($countnew > 0
            ? '<a href="index.php?act=files&amp;new' . $lnk . '">' . $lng['new_files'] . ' (' . $countnew . ')</a>'
            : $lng_forum['new_files_empty']) . '</p>';
    echo '<div class="phdr">' . $caption . '</div>';
    $link = array();
    $total = 0;
    for ($i = 1; $i < 10; $i++) {
        $count = $db->query("SELECT COUNT(*) FROM `cms_forum_files` WHERE `filetype` = '$i'" . ($rights >= 7
                                                  ? '' : " AND `del` != '1'") . $sql)->fetchColumn();
        if ($count > 0) {
            $link[] = '<img src="../images/system/' . $i . '.png" width="16" height="16" class="left" />&#160;<a href="index.php?act=files&amp;do=' . $i . $lnk . '">' . $types[$i] . '</a>&#160;(' . $count . ')';
            $total = $total + $count;
        }
    }
    foreach ($link as $var) {
        echo ($i % 2 ? '<div class="list2">' : '<div class="list1">') . $var . '</div>';
        ++$i;
    }
    echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
}
echo '<p>' . (($do || isset($_GET['new']))
        ? '<a href="index.php?act=files' . $lnk . '">' . $lng_forum['section_list'] . '</a><br />'
        : '') . '<a href="index.php' . ($id ? '?id=' . $id : '') . '">' . $lng['forum'] . '</a></p>';
