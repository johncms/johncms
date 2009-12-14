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

$textl = 'Форум-поиск';
$headmod = 'forumsearch';
require_once ('../incfiles/core.php');
require_once ('../incfiles/head.php');

echo '<div class="phdr"><b>Поиск по форуму</b></div>';

////////////////////////////////////////////////////////////
// Принимаем данные, выводим форму поиска                 //
////////////////////////////////////////////////////////////
$search = isset ($_POST['search']) ? trim($_POST['search']) : '';
$search = $search ? $search : rawurldecode(trim($_GET['search']));
$search = preg_replace("/[^\w\x7F-\xFF\s]/", " ", $search);
$search_t = isset ($_REQUEST['t']) ? 1 : 0;

echo '<div class="gmenu"><form action="search.php" method="post"><p>';
echo '<input type="text" value="' . ($search ? checkout($search) : '') . '" name="search" />';
echo '<input type="submit" value="Поиск" name="submit" /><br />';
echo '<input name="t" type="checkbox" value="1" ' . ($search_t ? 'checked="checked"' : '') . ' />&nbsp;Искать в названиях тем';
echo '</p></form></div>';

////////////////////////////////////////////////////////////
// Проверям на ошибки                                     //
////////////////////////////////////////////////////////////
$error = false;
if ($search && mb_strlen($search) < 4)
    $error = 'Общая длина поискового запроса должна быть не менее 4 букв.';
if ($search && mb_strlen($search) > 64)
    $error = 'Общая длина поискового запроса должна быть не более 64 букв.';

if ($search && !$error) {
    ////////////////////////////////////////////////////////////
    // Выводим результаты поиска                              //
    ////////////////////////////////////////////////////////////
    echo '<div class="bmenu">Результаты поиска</div>';
    $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum`
    WHERE MATCH (`text`) AGAINST ('" . mysql_real_escape_string($search) . "')
    AND `type` = '" . ($search_t ? 't' : 'm') . "'" . ($rights >= 7 ? "" :
    " AND `close` != '1'")), 0);
    if ($total) {
        $req = mysql_query("SELECT * FROM `forum` WHERE MATCH (`text`) AGAINST ('" . mysql_real_escape_string($search) . "') AND `type` = '" . ($search_t ? 't' : 'm') . "' LIMIT $start, $kmess");
        while ($res = mysql_fetch_assoc($req)) {
            echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
            if (!$search_t) {
                $req_t = mysql_query("SELECT `id`,`text` FROM `forum` WHERE `id` = '" . $res['refid'] . "' LIMIT 1");
                $res_t = mysql_fetch_assoc($req_t);
                echo '<b>' . $res_t['text'] . '</b><br />';
            }
            else {
                $req_p = mysql_query("SELECT `text` FROM `forum` WHERE `refid` = '" . $res['id'] . "' ORDER BY `id` ASC LIMIT 1");
                $res_p = mysql_fetch_assoc($req_p);
                echo '<b>' . $res['text'] . '</b><br />';
            }
            echo '<a href="../str/anketa.php?id=' . $res['user_id'] . '">' . $res['from'] . '</a> ';
            echo ' <span class="gray">(' . date("d.m.Y / H:i", $res['time'] + $set_user['sdvig'] * 3600) . ')</span><br/>';
            $text = $search_t ? $res_p['text'] : $res['text'];
            $text = checkout(mb_substr($text, 0, 400), 2, 1);
            $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);
            echo $text;
            if (mb_strlen($res['text']) > 400)
                echo '...<a href="index.php?act=post&amp;id=' . $res['id'] . '">Читать все &gt;&gt;</a>';
            echo '<br /><a href="index.php?id=' . ($search_t ? $res['id'] : $res_t['id']) . '">В тему</a>' . ($search_t ? '' : ' | <a href="index.php?act=post&amp;id=' . $res['id'] . '">К сообщению</a>');
            echo '</div>';
            ++$i;
        }
    }
    else {
        echo '<div class="rmenu"><p>По Вашему запросу ничего не найдено</p></div>';
    }
    echo '<div class="phdr">Всего совпадений: ' . $total . '</div>';
    if ($total > $kmess) {
        // Навигация по страницам
        echo '<p>' . pagenav('search.php?' . ($search_t ? 't=1&amp;' : '') . 'search=' . rawurlencode($search) . '&amp;', $start, $total, $kmess) . '</p>';
        echo '<p><form action="index.php" method="get"><input type="hidden" name="id" value="' . $id . '"/><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
    }
}
else {
    // Выводим сообщение об ошибке
    if ($error)
        echo '<div class="rmenu"><p>ОШИБКА!<br />' . $error . '</p></div>';
    // Инструкции для поиска
    echo
    '<div class="phdr"><small>Длина запроса: 4мин., 64макс.<br />Поиск нечувствителен к регистру букв<br />Результаты выводятся с сортировкой по релевантности</small></div>';
}

echo '<p>' . ($search ? '<a href="search.php">Новый поиск</a><br />' : '') . '<a href="index.php">Вернуться в Форум</a></p>';
require_once ('../incfiles/end.php');

?>