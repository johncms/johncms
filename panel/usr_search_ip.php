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

////////////////////////////////////////////////////////////
// Принимаем данные, выводим форму поиска                 //
////////////////////////////////////////////////////////////
$search = isset ($_POST['search']) ? trim($_POST['search']) : '';
$search = $search ? $search : rawurldecode(trim($_GET['search']));
if (isset ($_GET['ip']))
    $search = long2ip(intval($_GET['ip']));

echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Поиск по IP</div>';
echo '<form action="index.php?act=usr_search_ip" method="post"><div class="gmenu"><p>';
echo '<input type="text" name="search" value="' . checkout($search) . '" />';
echo '<input type="submit" value="Поиск" name="submit" /><br />';
echo '</p></div></form>';

if ($search) {
    ////////////////////////////////////////////////////////////
    // Проверям на ошибки                                     //
    ////////////////////////////////////////////////////////////
    $error = array();
    $ip = str_replace(' ', '', $search);    // Убираем пробелы
    if (stristr($ip, '-')) {
        ////////////////////////////////////////////////////////////
        // Обрабатываем диапазон адресов                          //
        ////////////////////////////////////////////////////////////
        if (!ereg("^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\-([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$", $ip))
            $error = 'Неправильно введен диапазон адресов IP';
        if (!$error) {
            $iparr = explode('-', $ip);
            $ip1 = ip2long($iparr[0]);
            $ip2 = ip2long($iparr[1]);
            $mode = 1;
            if (!$ip1)
                $error = '<div>Неправильно введен первый адрес</div>';
            if (!$ip2)
                $error .= '<div>Неправильно введен второй адрес</div>';
            if (!$error && $ip1 > $ip2)
                $error = 'Второй адрес должен быть больше первого';
        }
    }
    elseif (stristr($ip, '*')) {
        ////////////////////////////////////////////////////////////
        // Обрабатываем адреса с маской                           //
        ////////////////////////////////////////////////////////////
        $iptmp = explode('*', $ip);
        $ip = eregi_replace(".$", "", $iptmp[0]);        // Убираем точку в конце
        $iparr = explode('.', $ip);        // Разбиваем по частям
        if (isset ($iparr[2])) {
            $ip1 = $iparr[0] . '.' . $iparr[1] . '.' . $iparr[2] . '.0';
            $ip2 = $iparr[0] . '.' . $iparr[1] . '.' . $iparr[2] . '.255';
        }
        elseif (isset ($iparr[1])) {
            $ip1 = $iparr[0] . '.' . $iparr[1] . '.0.0';
            $ip2 = $iparr[0] . '.' . $iparr[1] . '.255.255';
        }
        else {
            $ip1 = $iparr[0] . '.0.0.0';
            $ip2 = $iparr[0] . '.255.255.255';
        }
        $ip1 = ip2long($ip1);
        $ip2 = ip2long($ip2);
        $mode = 2;
        if (!$ip1)
            $error = '<div>Неправильно введен адрес</div>';
    }
    else {
        ////////////////////////////////////////////////////////////
        // Обрабатываем одиночный адрес                           //
        ////////////////////////////////////////////////////////////
        if (!ereg("^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$", $ip))
            $error = 'Неправильно введен адрес IP';
        $ip = ip2long($ip);
        if (!$error && !$ip)
            $error = 'Неправильно введен адрес IP';
        if (!$error) {
            $ip1 = $ip;
            $ip2 = $ip;
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
    }
    else {
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
}
else {
    // Выводим сообщение об ошибке
    if ($error)
        echo '<div class="rmenu"><p>ОШИБКА!<br />' . $error . '</p></div>';
    // Инструкции для поиска
    echo '<div class="phdr"><small><b>Примеры запросов:</b><br /><font color="#FF0000">10.5.7.1</font> - Поиск одного адреса<br />';
    echo '<font color="#FF0000">10.5.7.1-10.5.7.100</font> - Поиск по диапазону адресов (знак маски * использовать нельзя)<br />';
    echo '<font color="#FF0000">10.5.*.*</font> - Поиск по маске. Будет найдена вся подсеть, начиная с адреса 0 и заканчивая 255';
    echo '</small></div>';
}

?>