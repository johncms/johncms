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

$headmod = 'forumfiles';
require('../system/head.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

$types = [
    1 => _t('Windows applications'),
    2 => _t('Java applications'),
    3 => _t('SIS'),
    4 => _t('txt'),
    5 => _t('Pictures'),
    6 => _t('Archive'),
    7 => _t('Videos'),
    8 => _t('MP3'),
    9 => _t('Other'),
];
$new = time() - 86400; // Сколько времени файлы считать новыми?

// Получаем ID раздела и подготавливаем запрос
$c = isset($_GET['c']) ? abs(intval($_GET['c'])) : false; // ID раздела
$s = isset($_GET['s']) ? abs(intval($_GET['s'])) : false; // ID подраздела
$t = isset($_GET['t']) ? abs(intval($_GET['t'])) : false; // ID топика
$do = isset($_GET['do']) && intval($_GET['do']) > 0 && intval($_GET['do']) < 10 ? intval($_GET['do']) : 0;

if ($c) {
    $id = $c;
    $lnk = '&amp;c=' . $c;
    $sql = " AND `cat` = '" . $c . "'";
    $caption = '<b>' . _t('Category Files') . '</b>: ';
    $input = '<input type="hidden" name="c" value="' . $c . '"/>';
} elseif ($s) {
    $id = $s;
    $lnk = '&amp;s=' . $s;
    $sql = " AND `subcat` = '" . $s . "'";
    $caption = '<b>' . _t('Section files') . '</b>: ';
    $input = '<input type="hidden" name="s" value="' . $s . '"/>';
} elseif ($t) {
    $id = $t;
    $lnk = '&amp;t=' . $t;
    $sql = " AND `topic` = '" . $t . "'";
    $caption = '<b>' . _t('Topic Files') . '</b>: ';
    $input = '<input type="hidden" name="t" value="' . $t . '"/>';
} else {
    $id = false;
    $sql = '';
    $lnk = '';
    $caption = '<b>' . _t('Forum Files') . '</b>';
    $input = '';
}

if ($c || $s || $t) {
    // Получаем имя нужной категории форума
    $req = $db->query("SELECT `text` FROM `forum` WHERE `id` = '$id'");

    if ($req->rowCount()) {
        $res = $req->fetch();
        $caption .= $res['text'];
    } else {
        echo $tools->displayError(_t('Wrong data'), '<a href="index.php">' . _t('Forum') . '</a>');
        require('../system/end.php');
        exit;
    }
}

if ($do || isset($_GET['new'])) {
    // Выводим список файлов нужного раздела
    $total = $db->query("SELECT COUNT(*) FROM `cms_forum_files` WHERE " . (isset($_GET['new']) ? " `time` > '$new'" : " `filetype` = '$do'") . $sql)->fetchColumn();

    if ($total) {
        // Заголовок раздела
        echo '<div class="phdr">' . $caption . (isset($_GET['new']) ? '<br />' . _t('New Files') : '') . '</div>' . ($do ? '<div class="bmenu">' . $types[$do] . '</div>' : '');
        $req = $db->query("SELECT `cms_forum_files`.*, `forum`.`user_id`, `forum`.`text`, `topicname`.`text` AS `topicname`
            FROM `cms_forum_files`
            LEFT JOIN `forum` ON `cms_forum_files`.`post` = `forum`.`id`
            LEFT JOIN `forum` AS `topicname` ON `cms_forum_files`.`topic` = `topicname`.`id`
            WHERE " . (isset($_GET['new']) ? " `cms_forum_files`.`time` > '$new'" : " `filetype` = '$do'") . ($systemUser->rights >= 7 ? '' : " AND `del` != '1'") . $sql .
            "ORDER BY `time` DESC LIMIT $start,$kmess");

        for ($i = 0; $res = $req->fetch(); ++$i) {
            $res_u = $db->query("SELECT `id`, `name`, `sex`, `rights`, `lastdate`, `status`, `datereg`, `ip`, `browser` FROM `users` WHERE `id` = '" . $res['user_id'] . "'")->fetch();
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            // Выводим текст поста
            $text = mb_substr($res['text'], 0, 500);
            $text = $tools->checkout($text, 1, 0);
            $text = preg_replace('#\[c\](.*?)\[/c\]#si', '', $text);
            $page = ceil($db->query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['topic'] . "' AND `id` " . ($set_forum['upfp'] ? ">=" : "<=") . " '" . $res['post'] . "'")->fetchColumn() / $kmess);
            $text = '<b><a href="index.php?id=' . $res['topic'] . '&amp;page=' . $page . '">' . $res['topicname'] . '</a></b><br />' . $text;

            if (mb_strlen($res['text']) > 500) {
                $text .= '<br /><a href="index.php?act=post&amp;id=' . $res['post'] . '">' . _t('Read more') . ' &gt;&gt;</a>';
            }

            // Формируем ссылку на файл
            $fls = @filesize('../files/forum/attach/' . $res['filename']);
            $fls = round($fls / 1024, 0);
            $att_ext = strtolower(pathinfo('./files/forum/attach/' . $res['filename'], PATHINFO_EXTENSION));
            $pic_ext = [
                'gif',
                'jpg',
                'jpeg',
                'png',
            ];

            if (in_array($att_ext, $pic_ext)) {
                // Если картинка, то выводим предпросмотр
                $file = '<div><a href="index.php?act=file&amp;id=' . $res['id'] . '">';
                $file .= '<img src="thumbinal.php?file=' . (urlencode($res['filename'])) . '" alt="' . _t('Click to view image') . '" /></a></div>';
            } else {
                // Если обычный файл, выводим значок и ссылку
                $file = ($res['del'] ? '<img src="../images/del.png" width="16" height="16" />'
                        : '') . '<img src="../images/system/' . $res['filetype'] . '.png" width="16" height="16" />&#160;';
            }

            $file .= '<a href="index.php?act=file&amp;id=' . $res['id'] . '">' . htmlspecialchars($res['filename']) . '</a><br />';
            $file .= '<small><span class="gray">' . _t('Size') . ': ' . $fls . ' kb.<br />' . _t('Downloaded') . ': ' . $res['dlcount'] . ' ' . _t('Time') . '</span></small>';
            $arg = [
                'iphide' => 1,
                'sub'    => $file,
                'body'   => $text,
            ];

            echo $tools->displayUser($res_u, $arg);
            echo '</div>';
        }

        echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

        if ($total > $kmess) {
            // Постраничная навигация
            echo '<p>' . $tools->displayPagination('index.php?act=files&amp;' . (isset($_GET['new']) ? 'new'
                        : 'do=' . $do) . $lnk . '&amp;', $start, $total, $kmess) . '</p>' .
                '<p><form action="index.php" method="get">' .
                '<input type="hidden" name="act" value="files"/>' .
                '<input type="hidden" name="do" value="' . $do . '"/>' . $input . '<input type="text" name="page" size="2"/>' .
                '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
        }
    } else {
        echo '<div class="list1">' . _t('The list is empty') . '</div>';
    }
} else {
    // Выводим список разделов, в которых есть файлы
    $countnew = $db->query("SELECT COUNT(*) FROM `cms_forum_files` WHERE `time` > '$new'" . ($systemUser->rights >= 7 ? '' : " AND `del` != '1'") . $sql)->fetchColumn();
    echo '<p>' . ($countnew > 0
            ? '<a href="index.php?act=files&amp;new' . $lnk . '">' . _t('New Files') . ' (' . $countnew . ')</a>'
            : _t('No new files')) . '</p>';
    echo '<div class="phdr">' . $caption . '</div>';
    $link = [];
    $total = 0;
    for ($i = 1; $i < 10; $i++) {
        $count = $db->query("SELECT COUNT(*) FROM `cms_forum_files` WHERE `filetype` = '$i'" . ($systemUser->rights >= 7 ? '' : " AND `del` != '1'") . $sql)->fetchColumn();

        if ($count > 0) {
            $link[] = '<img src="../images/system/' . $i . '.png" width="16" height="16" class="left" />&#160;<a href="index.php?act=files&amp;do=' . $i . $lnk . '">' . $types[$i] . '</a>&#160;(' . $count . ')';
            $total = $total + $count;
        }
    }

    foreach ($link as $var) {
        echo ($i % 2 ? '<div class="list2">' : '<div class="list1">') . $var . '</div>';
        ++$i;
    }

    echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';
}

echo '<p>' . (($do || isset($_GET['new']))
        ? '<a href="index.php?act=files' . $lnk . '">' . _t('List of sections') . '</a><br />'
        : '') . '<a href="index.php' . ($id ? '?id=' . $id : '') . '">' . _t('Forum') . '</a></p>';
