<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                                                                    //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@johncms.com                     //
// Олег Касьянов aka AlkatraZ          alkatraz@johncms.com                   //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

require_once ("../incfiles/head.php");
if (empty ($_GET['id'])) {
    echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
$s = intval($_GET['s']);
// Запрос сообщения
$req = mysql_query(
"SELECT `forum`.*, `users`.`sex`, `users`.`rights`, `users`.`lastdate`, `users`.`status`, `users`.`datereg`
FROM `forum` LEFT JOIN `users` ON `forum`.`user_id` = `users`.`id`
WHERE `forum`.`type` = 'm' AND `forum`.`id` = '$id'" . ($rights
>= 7 ? "" : " AND `forum`.`close` != '1'") . " LIMIT 1");
$res = mysql_fetch_array($req);

// Запрос темы
$them = mysql_fetch_array(mysql_query("SELECT * FROM `forum` WHERE `type` = 't' AND `id` = '" . $res['refid'] . "'"));
echo '<div class="phdr"><b>Тема:</b> ' . $them['text'] . '</div><div class="menu">';
// Значок пола
if ($res['sex'])
    echo '<img src="../theme/' . $set_user['skin'] . '/images/' . ($res['sex'] == 'm' ? 'm' : 'w') . '.png" alt=""  width="16" height="16"/>&nbsp;';
else
    echo '<img src="../images/del.png" width="12" height="12" />&nbsp;';
// Ник юзера и ссылка на его анкету
if ($user_id && $user_id != $res['user_id']) {
    echo '<a href="../str/anketa.php?id=' . $res['user_id'] . '&amp;fid=' . $res['id'] . '"><b>' . $res['from'] . '</b></a> ';
    echo '<a href="index.php?act=say&amp;id=' . $res['id'] . '&amp;start=' . $start . '"> [о]</a> <a href="index.php?act=say&amp;id=' . $res['id'] . '&amp;start=' . $start . '&amp;cyt"> [ц]</a>';
}
else {
    echo '<b>' . $res['from'] . '</b>';
}
// Метка должности
switch ($res['rights']) {
    case 7 :
        echo " Adm ";
        break;
    case 6 :
        echo " Smd ";
        break;
    case 3 :
        echo " Mod ";
        break;
    case 1 :
        echo " Kil ";
        break;
}
// Метка Онлайн / Офлайн
echo ($realtime > $res['lastdate'] + 300 ? '<span class="red"> [Off]</span>' : '<span class="green"> [ON]</span>');
// Время поста
echo ' <span class="gray">(' . date("d.m.Y / H:i", $res['time'] + $set_user['sdvig'] * 3600) . ')</span><br/>';
// Статус юзера
if (!empty ($res['status']))
    echo '<div class="status"><img src="../theme/' . $set_user['skin'] . '/images/star.gif" alt=""/>&nbsp;' . $res['status'] . '</div>';
$text = htmlentities($res['text'], ENT_QUOTES, 'UTF-8');
$text = nl2br($text);
$text = tags($text);
if ($set_user['smileys'])
    $text = smileys($text, ($res['rights'] >= 1) ? 1 : 0);
echo $text . '</div>';
// Вычисляем, на какой странице сообщение?
$page = ceil(mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['refid'] . "' AND `id` " . ($set_forum['upfp'] ? ">=" : "<=") . " '$id'"), 0) / $kmess);
echo '<div class="phdr"><a href="index.php?id=' . $res['refid'] . '&amp;page=' . $page . '">Вернуться в тему</a></div>';
echo '<p><a href="index.php">В форум</a></p>';

?>