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

$headmod = 'sitetop';
$textl = 'Топ активности сайта';
require_once ('../incfiles/core.php');
require_once ('../incfiles/head.php');

function get_top($order = 'postforum') {
    $req = mysql_query("SELECT * FROM `users` WHERE `$order` > 0 ORDER BY `$order` DESC LIMIT 9");
    if (mysql_num_rows($req)) {
        $out = '';
        while ($res = mysql_fetch_assoc($req)) {
            $out .= ($i % 2) ? '<div class="list2">' : '<div class="list1">';
            $out .= show_user($res, 1, 0, ' (' . $res[$order] . ')') . '</div>';
            ++$i;
        }
        return $out;
    }
    else {
        return '<div class="menu"><p>Список пуст</p></div>';
    }
}

////////////////////////////////////////////////////////////
// Показываем топ                                         //
////////////////////////////////////////////////////////////
$top_karma = $set_karma['on'] ? ' | <a href="users_top.php?act=karma">Карма</a>' : '';
switch ($act) {
    case 'guest':
        echo '<p><a href="users_top.php?act=forum">Форум</a> | Гостевая | <a href="users_top.php?act=chat">Чат</a> | <a href="users_top.php?act=vic">Викторина</a> | <a href="users_top.php?act=bal">Баланс</a> | <a href="users_top.php?act=kom">Комментарии</a>' . $top_karma . '</p>';
        echo '<div class="phdr"><b>Самые активные в Гостевой</b></div>';
        echo get_top('postguest');
        echo '<div class="phdr"><a href="../str/guest.php">В Гостевую</a></div>';
        break;
    case 'chat':
        echo '<p><a href="users_top.php?act=forum">Форум</a> | <a href="users_top.php?act=guest">Гостевая</a> | Чат | <a href="users_top.php?act=vic">Викторина</a> | <a href="users_top.php?act=bal">Баланс</a> | <a href="users_top.php?act=kom">Комментарии</a>' . $top_karma . '</p>';
        echo '<div class="phdr"><b>Самые активные в Чате</b></div>';
        echo get_top('postchat');
        echo '<div class="phdr"><a href="../chat/index.php">В Чат</a></div>';
        break;
    case 'vic':
        echo '<p><a href="users_top.php?act=forum">Форум</a> | <a href="users_top.php?act=guest">Гостевая</a> | <a href="users_top.php?act=chat">Чат</a> | Викторина | <a href="users_top.php?act=bal">Баланс</a> | <a href="users_top.php?act=kom">Комментарии</a>' . $top_karma . '</p>';
        echo '<div class="phdr"><b>Лучшие &quot;умники&quot; Викторины</b></div>';
        echo get_top('otvetov');
        echo '<div class="phdr"><a href="../chat/index.php">В Чат</a></div>';
        break;
    case 'bal':
        echo '<p><a href="users_top.php?act=forum">Форум</a> | <a href="users_top.php?act=guest">Гостевая</a> | <a href="users_top.php?act=chat">Чат</a> | <a href="users_top.php?act=vic">Викторина</a> | Баланс | <a href="users_top.php?act=kom">Комментарии</a>' . $top_karma . '</p>';
        echo '<div class="phdr"><b>Самые большие игровые Балансы</b></div>';
        echo get_top('balans');
        echo '<div class="phdr"><a href="../index.php">На Главную</a></div>';
        break;
    case 'kom':
        echo '<p><a href="users_top.php?act=forum">Форум</a> | <a href="users_top.php?act=guest">Гостевая</a> | <a href="users_top.php?act=chat">Чат</a> | <a href="users_top.php?act=vic">Викторина</a> | <a href="users_top.php?act=bal">Баланс</a> | Комментарии' . $top_karma . '</p>';
        echo '<div class="phdr"><b>Больше всего комментировали</b></div>';
        echo get_top('komm');
        echo '<div class="phdr"><a href="../index.php">На Главную</a></div>';
        break;
    case 'karma':
        if ($set_karma['on']) {
            echo '<p><a href="users_top.php?act=forum">Форум</a> | <a href="users_top.php?act=guest">Гостевая</a> | <a href="users_top.php?act=chat">Чат</a> | <a href="users_top.php?act=vic">Викторина</a> | <a href="users_top.php?act=bal">Баланс</a> | <a href="users_top.php?act=kom">Комментарии</a> | Карма</p>';
            echo '<div class="phdr"><b>Больше всего карма у ...</b></div>';
            echo get_top('karma');
            echo '<div class="phdr"><a href="../index.php">На Главную</a></div>';
        }
        break;
    default:
        echo '<p>Форум | <a href="users_top.php?act=guest">Гостевая</a> | <a href="users_top.php?act=chat">Чат</a> | <a href="users_top.php?act=vic">Викторина</a> | <a href="users_top.php?act=bal">Баланс</a> | <a href="users_top.php?act=kom">Комментарии</a>' . $top_karma . '</p>';
        echo '<div class="phdr"><b>Самые активные на Форуме</b></div>';
        echo get_top('postforum');
        echo '<div class="phdr"><a href="../forum/index.php">В Форум</a></div>';
}

echo '<p><a href="../index.php?act=users">Актив Сайта</a></p>';
require_once ('../incfiles/end.php');

?>