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

defined('_IN_JOHNADM') or die('Error: restricted access');

$error = array ();
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$search = $search ? $search : rawurldecode(trim($_GET['search']));
if (isset($_GET['ip']))
    $search = long2ip(intval($_GET['ip']));

    echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Поиск по IP</div>';
echo '<form action="index.php?act=usr_search_ip" method="post"><div class="gmenu"><p>';
echo '<input type="text" name="search" value="' . checkout($search) . '" />';
echo '<input type="submit" value="Поиск" name="submit" /><br />';
echo '</p></div></form>';

if ($search) {
    if (strstr($search, '-')) {
        ////////////////////////////////////////////////////////////
        // Обрабатываем диапазон адресов                          //
        ////////////////////////////////////////////////////////////
        $array = explode('-', $search);
        $ip = trim($array[0]);
        if (!ip_valid($ip))
            $error[] = 'Первый адрес введен неверно';
        else
            $ip1 = ip2long($ip);
        $ip = trim($array[1]);
        if (!ip_valid($ip))
            $error[] = 'Второй адрес введен неверно';
        else
            $ip2 = ip2long($ip);
    } elseif (strstr($search, '*')) {
        ////////////////////////////////////////////////////////////
        // Обрабатываем адреса с маской                           //
        ////////////////////////////////////////////////////////////
        $array = explode('.', $search);
        for ($i = 0; $i < 4; $i++) {
            if (!isset($array[$i]) || $array[$i] == '*') {
                $ipt1[$i] = '0';
                $ipt2[$i] = '255';
            } elseif (is_numeric($array[$i]) && $array[$i] >= 0 && $array[$i] <= 255) {
                $ipt1[$i] = $array[$i];
                $ipt2[$i] = $array[$i];
            } else {
                $error = 'Адрес введен неверно';
            }
            $ip1 = ip2long($ipt1[0] . '.' . $ipt1[1] . '.' . $ipt1[2] . '.' . $ipt1[3]);
            $ip2 = ip2long($ipt2[0] . '.' . $ipt2[1] . '.' . $ipt2[2] . '.' . $ipt2[3]);
        }
    } else {
        ////////////////////////////////////////////////////////////
        // Обрабатываем одиночный адрес                           //
        ////////////////////////////////////////////////////////////
        if (!ip_valid($search)) {
            $error = 'Адрес введен неверно';
        } else {
            $ip1 = ip2long($search);
            $ip2 = $ip1;
        }
    }
}

if ($search && !$error) {
    ////////////////////////////////////////////////////////////
    // Выводим результаты поиска                              //
    ////////////////////////////////////////////////////////////
    echo '<div class="phdr">Результаты запроса</div>';
    $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `ip` BETWEEN $ip1 AND $ip2"), 0);
    if ($total) {
        $req = mysql_query("SELECT * FROM `users` WHERE `ip` BETWEEN $ip1 AND $ip2 ORDER BY `name` ASC LIMIT $start, $kmess");
        while ($res = mysql_fetch_array($req)) {
            echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
            echo show_user($res, 1, ($rights >= 6 ? 2 : 0));
            echo '</div>';
            ++$i;
        }
    } else {
        echo '<div class="menu"><p>По Вашему запросу ничего не найдено</p></div>';
    }
    echo '<div class="phdr">Всего найдено: ' . $total . '</div>';
    if ($total > $kmess) {
        // Навигация по страницам
        echo '<p>' . pagenav('index.php?act=usr_search_ip&amp;' . ($search_t ? 't=1&amp;' : '') . 'search=' . rawurlencode($search) . '&amp;', $start, $total, $kmess) . '</p>';
        echo '<p><form action="index.php?act=usr_search_ip" method="post"><input type="hidden" name="search" value="' . checkout($search) .
            '" /><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
    }
    echo '<p><a href="index.php?act=usr_search_ip">Новый поиск</a></p>';
} else {
    // Выводим сообщение об ошибке
    if ($error)
        echo display_error($error);
    // Инструкции для поиска
    echo '<div class="phdr"><small><b>Примеры запросов:</b><br /><font color="#FF0000">10.5.7.1</font> - Поиск одного адреса<br />';
    echo '<font color="#FF0000">10.5.7.1-10.5.7.100</font> - Поиск по диапазону адресов (знак маски * использовать нельзя)<br />';
    echo '<font color="#FF0000">10.5.*.*</font> - Поиск по маске. Будет найдена вся подсеть, начиная с адреса 0 и заканчивая 255';
    echo '</small></div>';
}

?>