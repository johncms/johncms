<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_JOHNCMS', 1);

$headmod = 'anketa';
require_once ('../incfiles/core.php');

if (!$user_id) {
    require_once ('../incfiles/head.php');
    echo display_error('Только для зарегистрированных посетителей');
    require_once ('../incfiles/end.php');
    exit;
}

if ($id && $id != $user_id) {
    // Если был запрос на юзера, то получаем его данные
    $req = mysql_query("SELECT * FROM `users` WHERE `id` = '$id' LIMIT 1");
    if (mysql_num_rows($req)) {
        $user = mysql_fetch_assoc($req);
        $textl = 'Анкета: ' . $user['name'];
    }
    else {
        require_once ('../incfiles/head.php');
        echo display_error('Такого пользователя не существует');
        require_once ("../incfiles/end.php");
        exit;
    }
}
else {
    $id = false;
    $textl = 'Личная анкета';
    $user = $datauser;
}

require_once ('../incfiles/head.php');

////////////////////////////////////////////////////////////
// Выводим анкету пользователя                            //
////////////////////////////////////////////////////////////
echo '<div class="phdr"><b>' . ($id ? 'Анкета пользователя' : 'Моя анкета') . '</b></div>';
if ($user['dayb'] == $day && $user['monthb'] == $mon) {
    echo '<div class="gmenu">ИМЕНИНЫ!!!</div>';
}
echo '<div class="gmenu"><p><h3><img src="../theme/' . $set_user['skin'] . '/images/' . ($user['sex'] == 'm' ? 'm' : 'w') . ($user['datereg'] > $realtime - 86400 ? '_new' : '') . '.png" width="16" height="16" class="left" />&nbsp;';
echo '<b>' . $user['name'] . '</b> (id: ' . $user['id'] . ')';
if ($realtime > $user['lastdate'] + 300) {
    echo '<span class="red"> [Off]</span>';
    $lastvisit = date("d.m.Y (H:i)", $user['lastdate']);
}
else {
    echo '<span class="green"> [ON]</span>';
}
echo '</h3><ul>';
// Показываем аватар (если есть)
if (file_exists(('../files/avatar/' . $user['id'] . '.png'))) {
    echo '<li>Аватар:<br /><img src="../files/avatar/' . $user['id'] . '.png" width="32" height="32" alt="' . $user['name'] . '" /></li>';
}
// Показываем фотографию (если есть)
if (file_exists(('../files/photo/' . $user['id'] . '_small.jpg'))) {
    echo '<li>Фотография:<br /><a href="../files/photo/' . $user['id'] . '.jpg"><img src="../files/photo/' . $user['id'] . '_small.jpg" alt="' . $user['name'] . '" border="0" /></a></li>';
}
if (!empty($user['status']))
    echo '<li><span class="gray">Статус: </span>' . $user['status'] . '</li>';
echo '<li><span class="gray">Логин:</span> <b>' . $user['name_lat'] . '</b></li>';
if ($user['rights']) {
    echo '<li><span class="gray">Должность:</span> ';
    $rank = array(1 => 'Киллер', 2 => 'Модер Чата', 3 => 'Модер Форума', 4 => 'Модер Загрузок', 5 => 'Модер Библиотеки', 6 => 'Супермодератор', 7 => 'Администратор', 9 => 'Супервизор');
    echo '<span class="red"><b>' . $rank[$user['rights']] . '</b></span>';
    echo '</li>';
}
if (isset($lastvisit))
    echo '<li><span class="gray">Последний визит:</span> ' . $lastvisit . '</li>';
if ($rights >= 1 && $rights >= $user['rights']) {
    echo '<li><span class="gray">UserAgent:</span> ' . $user['browser'] . '</li>';
    echo '<li><span class="gray">Адрес IP:</span> ' . long2ip($user['ip']) . '</li>';
    if ($user['immunity'])
        echo '<li><span class="green"><b>ИММУНИТЕТ</b></span></li>';
}
echo '</ul></p></div>';

// Блок Кармы
if ($set_karma['on']) {
    echo '<div class="list2">';
    $exp = explode('|', $user['plus_minus']);
    if ($exp[0] > $exp[1]) {
        $karma = $exp[1] ? ceil($exp[0] / $exp[1]) : $exp[0];
        $images = $karma > 10 ? '2' : '1';
    }
    else
        if ($exp[1] > $exp[0]) {
            $karma = $exp[0] ? ceil($exp[1] / $exp[0]) : $exp[1];
            $images = $karma > 10 ? '-2' : '-1';
        }
        else {
            $images = 0;
        }
        echo '<table  width="100%"><tr><td width="22" valign="top"><img src="../images/k_' . $images . '.gif"/></td><td>';
    echo '<b>Карма (' . $user['karma'] . ')</b><div class="sub">
   <span class="green"><a href="karma.php?id=' . $id . '&amp;type=1">За (' . $exp[0] . ')</a></span> | <span class="red"><a href="karma.php?id=' . $id . '&amp;type=2">Против (' . $exp[1] . ')</a></span>';
    if ($id) {
        if (!$datauser['karma_off'] && (!$user['rights'] || ($user['rights'] && !$set_karma['adm'])) && $user['ip'] != $datauser['ip']) {
            $sum = mysql_result(mysql_query("SELECT SUM(`points`) FROM `karma_users` WHERE `user_id` = '$user_id' AND `time` >= '" . $datauser['karma_time'] . "'"), 0);
            $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `karma_users` WHERE `user_id` = '$user_id' AND `karma_user` = '$id' AND `time` > '" . ($realtime - 86400) . "'"), 0);
            if ($datauser['postforum'] >= $set_karma['forum'] && $datauser['total_on_site'] >= $set_karma['karma_time'] && ($set_karma['karma_points'] - $sum) > 0 && !$count) {
                echo '<br /><a href="karma.php?act=user&amp;id=' . $id . '">Отдать голос</a>';
            }
        }
    }
    else {
        $total_karma = mysql_result(mysql_query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '$user_id' AND `time` > " . ($realtime - 86400)), 0);
        if ($total_karma > 0)
            echo '<br /><a href="karma.php?act=new">Новые отзывы</a> (' . $total_karma . ')';
    }
    echo '</div></td></tr></table></div>';
}

// Личные данные
echo '<div class="menu">';
$out = '';
$req = mysql_query("select * from `gallery` where `type`='al' and `user`=1 and `avtor`='" . $user['name'] . "' LIMIT 1");
if (mysql_num_rows($req)) {
    $res = mysql_fetch_array($req);
    $out .= '<li><a href="../gallery/index.php?id=' . $res['id'] . '">Личный альбом</a></li>';
}
if (!empty($user['imname']))
    $out .= '<li><span class="gray">Имя:</span> ' . $user['imname'] . '</li>';
if (!empty($user['dayb']))
    $out .= '<li><span class="gray">Дата рождения:</span> ' . $user['dayb'] . '&nbsp;' . $mesyac[$user['monthb']] . '&nbsp;' . $user['yearofbirth'] . '</li>';
if (!empty($user['live']))
    $out .= '<li><span class="gray">Город:</span> ' . $user['live'] . '</li>';
if (!empty($user['about']))
    $out .= '<li><span class="gray">О себе:<br /></span> ' . smileys(tags($user['about'])) . '</li>';
if (!empty($out)) {
    echo '<p><h3><img src="../images/contacts.png" width="16" height="16" class="left" />&nbsp;Личные данные</h3><ul>';
    echo $out;
    echo '</ul></p>';
}
// Связь
$out = '';
if (!empty($user['mibile']))
    $out .= '<li><span class="gray">Тел. номер:</span> ' . $user['mibile'] . '</li>';
if (!empty($user['mail']) && (($id && $user['mailvis']) || !$id || $rights >= 7)) {
    $out .= '<li><span class="gray">E-mail:</span> ' . $user['mail'];
    $out .= ($user['mailvis'] ? '' : '<span class="gray"> [скрыт]</span>') . '</li>';
}
if (!empty($user['icq']))
    $out .= '<li><span class="gray">ICQ:</span>&nbsp;<img src="http://web.icq.com/whitepages/online?icq=' . $user['icq'] . '&amp;img=5" width="18" height="18" alt="icq" align="middle"/>&nbsp;' . $user['icq'] . '</li>';
if (!empty($user['skype']))
    $out .= '<li><span class="gray">Skype:</span>&nbsp;' . $user['skype'] . '</li>';
if (!empty($user['jabber']))
    $out .= '<li><span class="gray">Jabber:</span>&nbsp;' . $user['jabber'] . '</li>';
if (!empty($user['www']))
    $out .= '<li><span class="gray">Сайт:</span> ' . tags($user['www']) . '</li>';
if (!empty($out)) {
    echo '<p><h3><img src="../images/mail.png" width="16" height="16" class="left" />&nbsp;Связь</h3><ul>';
    echo $out;
    echo '</ul></p>';
}
// Статистика
echo '<p><h3><img src="../images/rate.gif" width="16" height="16" class="left" />&nbsp;Статистика</h3><ul>';
if ($rights >= 7) {
    if (!$user['preg'] && empty($user['regadm']))
        echo '<li>Ожидает подтверждения регистрации</li>';
    elseif (!$user['preg'] && !empty($user['regadm']))
        echo '<li>Регистрацию отклонил ' . $user['regadm'] . '</li>';
    elseif ($user['preg'] && !empty($user['regadm']))
        echo '<li>Регистрацию подтвердил ' . $user['regadm'] . '</li>';
    else
        echo '<li>Свободная регистрация</li>';
}
echo '<li><span class="gray">' . ($user['sex'] == 'm' ? 'Зарегистрирован' : 'Зарегистрирована') . ':</span> ' . date("d.m.Y", $user['datereg']) . '</li>';
echo '<li><span class="gray">' . ($user['sex'] == 'm' ? 'Пробыл' : 'Пробыла') . ' на сайте:</span> ' . timecount($user['total_on_site']) . '</li>';
echo '<li><a href="my_stat.php?id=' . $user['id'] . '">Статистика активности</a></li>';
echo '<li><a href="my_stat.php?act=forum' . ($id ? '&amp;id=' . $id : '') . '">Последние записи</a></li>';
// Если были нарушения, показываем ссылку на их историю
$ban = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `user_id` = '" . $user['id'] . "'"), 0);
if ($ban)
    echo '<li><a href="users_ban.php' . ($id && $id != $user_id ? '?id=' . $user['id'] : '') . '">Нарушения</a>&nbsp;<span class="red">(' . $ban . ')</span></li>';
echo '</ul></p></div>';
echo '<div class="phdr">' . (!$id || $id == $user_id || $rights >= 7 ? '<a href="my_data.php' . ($id ? '?id=' . $id : '') . '">Редактировать</a>' : '&nbsp;');
if ($id && !$user['immunity'] && $id != $user_id && $rights > $user['rights']) {
    if ($rights >= 7)
        echo ' | ';
    echo '<a href="users_ban.php?act=ban&amp;id=' . $user['id'] . '">Банить</a>';
    if ($rights >= 7)
        echo ' | <a href="../' . $admp . '/index.php?act=usr_del&amp;id=' . $user['id'] . '">Удалить</a><br/>';
}
echo '</div>';
if ($id && $id != $user_id) {
    echo '<p>';
    // Контакты
    $contacts = mysql_query("select * from `privat` where me='" . $login . "' and cont='" . $user['name'] . "'");
    $conts = mysql_num_rows($contacts);
    if ($conts != 1)
        echo "<a href='cont.php?act=edit&amp;id=" . $id . "&amp;add=1'>Добавить в контакты</a><br/>";
    else
        echo "<a href='cont.php?act=edit&amp;id=" . $id . "'>Удалить из контактов</a><br/>";
    // Игнор
    $igns = mysql_query("select * from `privat` where me='" . $login . "' and ignor='" . $user['name'] . "'");
    $ignss = mysql_num_rows($igns);
    if ($igns != 1) {
        if ($user['rights'] == 0 && $user['name'] != $nickadmina && $user['name'] != $nickadmina) {
            echo "<a href='ignor.php?act=edit&amp;id=" . $id . "&amp;add=1'>Добавить в игнор</a><br/>";
        }
    }
    else {
        echo "<a href='ignor.php?act=edit&amp;id=" . $id . "'>Удалить из игнора</a><br/>";
    }
    echo '<a href="pradd.php?act=write&amp;adr=' . $user['id'] . '">Написать в приват</a></p>';
}

require_once ('../incfiles/end.php');

?>