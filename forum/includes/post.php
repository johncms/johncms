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
if (!$id) {
    echo functions::display_error($lng['error_wrong_data']);
    require('../incfiles/end.php');
    exit;
}

// Запрос сообщения
$stmt = $db->query("SELECT `forum`.*, `users`.`sex`, `users`.`rights`, `users`.`lastdate`, `users`.`status`, `users`.`datereg`
FROM `forum` LEFT JOIN `users` ON `forum`.`user_id` = `users`.`id`
WHERE `forum`.`type` = 'm' AND `forum`.`id` = '$id'" . ($rights >= 7 ? "" : " AND `forum`.`close` != '1'") . " LIMIT 1");
if (!$stmt->rowCount()) {
    echo functions::display_error($lng['error_wrong_data']);
    require('../incfiles/end.php');
    exit;
}
$res = $stmt->fetch();

// Запрос темы
$them = $db->query("SELECT * FROM `forum` WHERE `type` = 't' AND `id` = '" . $res['refid'] . "' LIMIT 1")->fetch();
echo '<div class="phdr"><b>' . $lng_forum['topic'] . ':</b> ' . _e($them['text']) . '</div><div class="menu">';

// Данные пользователя
if ($set_user['avatar']) {
    echo '<table cellpadding="0" cellspacing="0"><tr><td>';
    if (file_exists(('../files/users/avatar/' . $res['user_id'] . '.png')))
        echo '<img src="../files/users/avatar/' . $res['user_id'] . '.png" width="32" height="32" alt="' . $res['from'] . '" />&#160;';
    else
        echo '<img src="../images/empty.png" width="32" height="32" alt="' . $res['from'] . '" />&#160;';
    echo '</td><td>';
}
if ($res['sex'])
    echo functions::image(($res['sex'] == 'm' ? 'm' : 'w') . ($res['datereg'] > time() - 86400 ? '_new' : '') . '.png', array('class' => 'icon-inline'));
else
    echo functions::image('del.png');
// Ник юзера и ссылка на его анкету
if ($user_id && $user_id != $res['user_id']) {
    echo '<a href="../users/profile.php?user=' . $res['user_id'] . '"><b>' . $res['from'] . '</b></a> ';
} else {
    echo '<b>' . $res['from'] . '</b> ';
}
// Метка должности
$user_rights = array(
    3 => '(FMod)',
    6 => '(Smd)',
    7 => '(Adm)',
    9 => '(SV!)'
);
echo @$user_rights[$res['rights']];
// Метка Онлайн / Офлайн
echo(time() > $res['lastdate'] + 300 ? '<span class="red"> [Off]</span> ' : '<span class="green"> [ON]</span> ');
echo '<a href="index.php?act=post&amp;id=' . $res['id'] . '" title="Link to post">[#]</a>';
// Ссылки на ответ и цитирование
if ($user_id && $user_id != $res['user_id']) {
    echo '&#160;<a href="index.php?act=say&amp;id=' . $res['id'] . '&amp;start=' . $start . '">' . $lng_forum['reply_btn'] . '</a>&#160;' .
        '<a href="index.php?act=say&amp;id=' . $res['id'] . '&amp;start=' . $start . '&amp;cyt">' . $lng_forum['cytate_btn'] . '</a> ';
}
// Время поста
echo ' <span class="gray">(' . functions::display_date($res['time']) . ')</span><br />';
// Статус юзера
if (!empty($res['status']))
    echo '<div class="status">' . functions::image('label.png', array('class' => 'icon-inline')) . $res['status'] . '</div>';
if ($set_user['avatar'])
    echo '</td></tr></table>';

// Вывод текста поста
$text = functions::checkout($res['text'], 1, 1);
if ($set_user['smileys']) {
    $text = functions::smileys($text, ($res['rights'] >= 1) ? 1 : 0);
}
echo $text;

// Если есть прикрепленный файл, выводим его описание
$stmt = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "'");
if ($stmt->rowCount()) {
    $fres = $stmt->fetch();
    $fls = round(@filesize('../files/forum/attach/' . $fres['filename']) / 1024, 2);
    echo '<div class="gray" style="font-size: x-small; background-color: rgba(128, 128, 128, 0.1); padding: 2px 4px; margin-top: 4px">' . $lng_forum['attached_file'] . ':';
    // Предпросмотр изображений
    $att_ext = strtolower(functions::format('./files/forum/attach/' . $fres['filename']));
    $pic_ext = array(
        'gif',
        'jpg',
        'jpeg',
        'png'
    );
    if (in_array($att_ext, $pic_ext)) {
        echo '<div><a href="index.php?act=file&amp;id=' . $fres['id'] . '">';
        echo '<img src="thumbinal.php?file=' . (urlencode($fres['filename'])) . '" alt="' . $lng_forum['click_to_view'] . '" /></a></div>';
    } else {
        echo '<br /><a href="index.php?act=file&amp;id=' . $fres['id'] . '">' . $fres['filename'] . '</a>';
    }
    echo ' (' . $fls . ' KB.)<br/>';
    echo $lng_forum['downloads'] . ': ' . $fres['dlcount'] . ' ' . $lng_forum['time'] . '</div>';
    $file_id = $fres['id'];
}

echo '</div>';

// Вычисляем, на какой странице сообщение?
$page = ceil($db->query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['refid'] . "' AND `id` " . ($set_forum['upfp'] ? ">=" : "<=") . " '$id'")->fetchColumn() / $kmess);
echo '<div class="phdr"><a href="index.php?id=' . $res['refid'] . '&amp;page=' . $page . '">' . $lng_forum['back_to_topic'] . '</a></div>';
echo '<p><a href="index.php">' . $lng['to_forum'] . '</a></p>';