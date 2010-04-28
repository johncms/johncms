<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.1.0                     30.05.2008                             //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Плагиат и удаление копирайтов заруганы на ближайших родственников!!!       //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNADM') or die('Error: restricted access');

if ($rights < 9)
    die('Error: restricted access');

    switch ($mod) {
    case 'new':
        ////////////////////////////////////////////////////////////
        // Баним IP адрес. Форма ввода данных и обработка         //
        ////////////////////////////////////////////////////////////
        echo '<div class="phdr"><b>Баним IP</b></div>';
        if (isset($_POST['submit'])) {
            $error = '';
            $ip = isset($_POST['ip']) ? trim($_POST['ip']) : '';
            $ban_term = isset($_POST['term']) ? intval($_POST['term']) : 1;
            $ban_url = isset($_POST['url']) ? htmlentities(trim($_POST['url']), ENT_QUOTES, 'UTF-8') : '';
            $reason = isset($_POST['reason']) ? htmlentities(trim($_POST['reason']), ENT_QUOTES, 'UTF-8') : '';
            if (empty($ip)) {
                echo '<p>ОШИБКА!<br />Не введен адрес IP<br /><a href="index.php?act=sys_ipban&amp;mod=new">Назад</a></p>';
                require_once("../incfiles/end.php");
                exit;
            }

            if (strstr($ip, '-')) {
                ////////////////////////////////////////////////////////////
                // Обрабатываем диапазон адресов                          //
                ////////////////////////////////////////////////////////////
                $mode = 1;
                $array = explode('-', $ip);
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
            } elseif (strstr($ip, '*')) {
                ////////////////////////////////////////////////////////////
                // Обрабатываем адреса с маской                           //
                ////////////////////////////////////////////////////////////
                $mode = 2;
                $array = explode('.', $ip);
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
                $mode = 3;
                if (!ip_valid($ip)) {
                    $error = 'Адрес введен неверно';
                } else {
                    $ip1 = ip2long($ip);
                    $ip2 = $ip1;
                }
            }

            if (!$error) {
                ////////////////////////////////////////////////////////////
                // Проверка на конфликты адресов                          //
                ////////////////////////////////////////////////////////////
                $req = mysql_query("SELECT * FROM `cms_ban_ip` WHERE ('" . $ip1 . "' BETWEEN `ip1` AND `ip2`) OR ('" . $ip2 . "' BETWEEN `ip1` AND `ip2`) OR (`ip1` >= '" . $ip1 . "' AND `ip2` <= '" . $ip2 . "')");
                $total = @mysql_num_rows($req);
                if ($total > 0) {
                    echo '<div class="rmenu"><p>Данные записи конфликтуют с введенными Вами адресами IP</p></div>';
                    while ($res = mysql_fetch_array($req)) {
                        echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
                        $ip = $res['ip1'] == $res['ip2'] ? long2ip($res['ip1']) : long2ip($res['ip1']) . ' - ' . long2ip($res['ip2']);
                        echo '<a href="index.php?act=sys_ipban&amp;mod=detail&amp;id=' . $res['id'] . '">' . $ip . '</a>';
                        switch ($res['ban_type']) {
                            case 2:
                                echo ' Редирект';
                                break;

                            case 3:
                                echo ' Регистрация';
                                break;

                            default:
                                echo ' <b>Блокировка</b>';
                        }
                        echo '</div>';
                        ++$i;
                    }
                    echo '<div class="phdr">Всего: ' . $total . '</div>';
                    echo '<p><a href="index.php?act=sys_ipban">Назад</a><br /><a href="index.php">Админ панель</a></p>';
                    require_once("../incfiles/end.php");
                    exit;
                }
            }
            // Проверяем, не попадает ли IP администратора в забаниваемый диапазон
            if ($ipl >= $ip1 && $ipl <= $ip2)
                $error = 'Ваш собственный адрес IP попадает в диапазон';
            if (!$error) {
                ////////////////////////////////////////////////////////////
                // Окно подтверждения                                     //
                ////////////////////////////////////////////////////////////
                echo '<form action="index.php?act=sys_ipban&amp;mod=insert" method="post">';
                echo '<div class="gmenu">Пожалуйста проверьте правильность введенных данных</div>';
                switch ($mode) {
                    case 1:
                        echo '<div class="menu"><p><u>Баним диапазон адресов</u><br />[<b>' . long2ip($ip1) . '</b>] - [<b>' . long2ip($ip2) . '</b>]</p>';
                        break;

                    case 2:
                        echo '<div class="menu"><p><u>По введенной маске, будет забанен диапазон адресов</u><br />[<b>' . long2ip($ip1) . '</b>] - [<b>' . long2ip($ip2) . '</b>]</p>';
                        break;

                    default:
                        echo '<div class="menu"><p><u>Баним адрес</u><br /><b>' . long2ip($ip1) . '</b></p>';
                }
                echo '<p><u>Тип бана</u><br />';
                switch ($ban_term) {
                    case 2:
                        echo 'Редирект</p><p><u>Адрес редиректа</u><br />' . (empty($ban_url) ? 'По умолчанию' : $ban_url);
                        break;

                    case 3:
                        echo 'Закрыта регистрация';
                        break;

                    default:
                        echo 'Блокировка';
                }
                echo '</p><p><u>Причина</u><br />' . (empty($reason) ? 'Не указана' : $reason) . '</p>';
                echo '<input type="hidden" value="' . $ip1 . '" name="ip1" />';
                echo '<input type="hidden" value="' . $ip2 . '" name="ip2" />';
                echo '<input type="hidden" value="' . $ban_term . '" name="term" />';
                echo '<input type="hidden" value="' . $ban_url . '" name="url" />';
                echo '<input type="hidden" value="' . $reason . '" name="reason" />';
                echo '</div><div class="bmenu"><input type="submit" name="submit" value="Банить"/></div>';
                echo '</form>';
                echo '<p><a href="index.php?act=sys_ipban">Отмена</a><br /><a href="index.php">Админ панель</a></p>';
            } else {
                echo display_error($error, '<a href="index.php?act=sys_ipban&amp;mod=new">Назад</a></');
            }
        } else {
            ////////////////////////////////////////////////////////////
            // Форма ввода IP адреса для Бана                         //
            ////////////////////////////////////////////////////////////
            echo '<form action="index.php?act=sys_ipban&amp;mod=new" method="post">';
            echo '<div class="menu"><u>Введите IP адрес</u><p>';
            echo '<input type="text" name="ip"/><br />';
            echo '<small>Банить можно как один адрес, диапазон адресов и по маске. Пример:<br />';
            echo '<font color="#FF0000">10.5.7.1</font> - Баним один адрес<br />';
            echo '<font color="#FF0000">10.5.7.1-10.5.7.100</font> - Баним по диапазону адресов.<br />';
            echo 'ВНИМАНИЕ! В диапазоне адресов нельзя использовать знак маски *<br />';
            echo '<font color="#FF0000">10.5.*.*</font> - Баним по маске. Будет забанена вся подсеть, начиная с адреса 0 и заканчивая 255';
            echo '</small></p></div>';
            echo '<div class="menu"><u>Тип бана</u><p>';
            echo '<input name="term" type="radio" value="1" checked="checked" />Блокировка<br />';
            echo '<input name="term" type="radio" value="3" />Закрыть регистрацию<br />';
            echo '<input name="term" type="radio" value="2" />Редирект<br /></p>';
            echo '<p><u>URL редиректа</u><br /><small>Необязательное поле</small><br />';
            echo '<input type="text" name="url"/><br />';
            echo '<small>Адрес вводите в формате http://url.com</small></p></div>';
            echo '<div class="menu"><u>Причина бана</u><br /><p><small>Необязательное поле</small><br />';
            echo '<textarea cols="20" rows="4" name="reason"></textarea></p></div>';
            echo '<div class="bmenu"><input type="submit" name="submit" value="Банить"/></div>';
            echo '</form>';
            echo '<p><a href="index.php?act=sys_ipban">Отмена</a><br /><a href="index.php">Админ панель</a></p>';
        }
        break;

    case 'insert':
        ////////////////////////////////////////////////////////////
        // Проверяем адрес и вставляем в базу                     //
        ////////////////////////////////////////////////////////////
        $ip1 = isset($_POST['ip1']) ? intval($_POST['ip1']) : '';
        $ip2 = isset($_POST['ip2']) ? intval($_POST['ip2']) : '';
        $ban_term = isset($_POST['term']) ? intval($_POST['term']) : 1;
        $ban_url = isset($_POST['url']) ? trim($_POST['url']) : '';
        $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
        if (!$ip1 || !$ip2) {
            echo '<p>ОШИБКА!<br />Адрес IP не указан<br /><a href="index.php">Админ панель</a></p>';
            require_once("../incfiles/end.php");
            exit;
        }
        mysql_query("INSERT INTO `cms_ban_ip` SET
        `ip1` = '$ip1',
        `ip2` = '$ip2',
        `ban_type` = '$ban_term',
        `link` = '" . check($ban_url) . "',
        `who` = '$login',
        `reason` = '" . check($reason) . "',
        `date` = '$realtime'");
        echo '<p>Бан добавлен в базу.</p>';
        echo '<p><a href="index.php?act=sys_ipban">Продолжить</a><br /><a href="index.php">Админ панель</a></p>';
        break;

    case 'clear':
        ////////////////////////////////////////////////////////////
        // Очистка таблицы банов по IP                            //
        ////////////////////////////////////////////////////////////
        if (isset($_GET['yes'])) {
            mysql_query("TRUNCATE TABLE `cms_ban_ip`;");
            echo '<p>Таблица IP банов успешно очищена.<br />Разбанены все адреса.</p>';
            echo '<p><a href="index.php?act=sys_ipban">Продолжить</a><br /><a href="index.php">Админ панель</a></p>';
        } else {
            echo '<p><b>ВНИМАНИЕ!</b><br />Таблица IP банов будет очищена.<br />Вы действительно хотите разбанить ВСЕ адреса IP?</p>';
            echo '<p><a href="index.php?act=sys_ipban">Отмена</a><br /><a href="index.php?act=sys_ipban&amp;mod=clear&amp;yes=yes">Да, разбанить</a></p><p><a href="index.php">Админ панель</a></p>';
        }
        break;

    case 'detail':
        echo '<div class="phdr">Блокированный IP</div>';
        if ($id) {
            ////////////////////////////////////////////////////////////
            // Поиск адреса по ссылке (ID)                            //
            ////////////////////////////////////////////////////////////
            $req = mysql_query("SELECT * FROM `cms_ban_ip` WHERE `id` = '" . $id . "' LIMIT 1");
            $ip = '';
        } elseif (isset($_POST['ip'])) {
            ////////////////////////////////////////////////////////////
            // Поиск адреса по запросу из формы                       //
            ////////////////////////////////////////////////////////////
            $ip = ip2long($_POST['ip']);
            if (!$ip) {
                echo '<p>ОШИБКА!<br />Адрес IP введен неверно<br /><a href="index.php">В Админку</a></p>';
                require_once("../incfiles/end.php");
                exit;
            }
            $req = mysql_query("SELECT * FROM `cms_ban_ip` WHERE '" . $ip . "' BETWEEN `ip1` AND `ip2` LIMIT 1");
        } else {
            echo '<p>ОШИБКА!<br /><a href="index.php">В Админку</a></p>';
            require_once("../incfiles/end.php");
            exit;
        }
        if (mysql_num_rows($req) != 1) {
            echo '<p>Такого адреса нет в базе.</p>';
            echo '<p><a href="index.php?act=sys_ipban">Назад</a><br /><a href="index.php">В Админку</a></p>';
            require_once("../incfiles/end.php");
            exit;
        } else {
            $res = mysql_fetch_array($req);
            $ip = $res['ip1'] == $res['ip2'] ? '<b>' . long2ip($res['ip1']) . '</b>' : '[<b>' . long2ip($res['ip1']) . '</b>] - [<b>' . long2ip($res['ip2']) . '</b>]';
            echo '<div class="rmenu"><p>' . $ip . '</p></div>';
            echo '<div class="menu"><p><u>Тип бана</u><br />';
            switch ($res['ban_type']) {
                case 2:
                    echo ' Редирект по ссылке.';
                    break;

                case 3:
                    echo ' Запрет регистрации.';
                    break;

                default:
                    echo ' Блокировка доступа к сайту.';
            }
            if ($res['ban_type'] == 2)
                echo '<br />Редирект: ' . $res['link'];
            echo '</p><p><u>Причина</u><br />' . (empty($res['reason']) ? 'Не указана' : $res['reason']) . '</p></div>';
            echo '<div class="menu">Банил: <b>' . $res['who'] . '</b><br />';
            echo 'Дата: <b>' . date('d.m.Y', $res['date']) . '</b><br />';
            echo 'Время: <b>' . date('H:i:s', $res['date']) . '</b></div>';
            echo '<div class="phdr"><a href="index.php?act=sys_ipban&amp;mod=del&amp;id=' . $res['id'] . '">Разбанить</a></div>';
            echo '<p><a href="index.php?act=sys_ipban">В список</a><br /><a href="index.php">Админ панель</a></p>';
        }
        break;

    case 'del':
        ////////////////////////////////////////////////////////////
        // Удаление выбранного IP из базы                         //
        ////////////////////////////////////////////////////////////
        if ($id) {
            if (isset($_GET['yes'])) {
                mysql_query("DELETE FROM `cms_ban_ip` WHERE `id`='$id' LIMIT 1");
                mysql_query("OPTIMIZE TABLE `cms_ban_ip`");
                echo '<p>Бан успешно удален из базы</p>';
                echo '<p><a href="index.php?act=sys_ipban">Продолжить</a><br /><a href="index.php">Админ панель</a></p>';
            } else {
                $req = mysql_query("SELECT * FROM `cms_ban_ip` WHERE `id`='" . $id . "' LIMIT 1");
                if (mysql_num_rows($req) != 1) {
                    echo '<p>Такого адреса нет в базе.</p>';
                    echo '<p><a href="index.php?act=sys_ipban">Назад</a><br /><a href="index.php">Админ панель</a></p>';
                    require_once("../incfiles/end.php");
                    exit;
                } else {
                    echo '<p>Вы действительно хотите разбанить адрес?</p>';
                    echo '<p><a href="index.php?act=sys_ipban&amp;mod=del&amp;id=' . $id . '&amp;yes=yes">Разбанить</a><br /><a href="index.php?act=sys_ipban&amp;mod=detail&amp;id=' . $id . '">Отмена</a></p>';
                }
            }
        }
        break;

    case 'search':
        ////////////////////////////////////////////////////////////
        // Форма поиска забаненного IP                            //
        ////////////////////////////////////////////////////////////
        echo '<div class="phdr"><b>Поиск блокированного адреса IP</b></div>';
        echo '<form action="index.php?act=sys_ipban&amp;mod=detail" method="post">';
        echo '<div class="menu"><u>Введите IP адрес</u>';
        echo '<p><input type="text" name="ip"/></p></div>';
        echo '<div class="bmenu"><input type="submit" name="submit" value="Поиск"/></div>';
        echo '</form>';
        echo '<p><a href="index.php?act=sys_ipban">Назад</a><br /><a href="index.php">Админ панель</a></p>';
        break;

    default:
        ////////////////////////////////////////////////////////////
        // Вывод общего списка забаненных IP                      //
        ////////////////////////////////////////////////////////////
        echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Бан по IP</div>';
        $req = mysql_query("SELECT COUNT(*) FROM `cms_ban_ip`");
        $total = mysql_result($req, 0);
        if ($total > 0) {
            $start = isset($_GET['page']) ? $page * $kmess - $kmess : $start;
            $req = mysql_query("SELECT * FROM `cms_ban_ip` ORDER BY `id` ASC LIMIT " . $start . "," . $kmess . ";");
            while ($res = mysql_fetch_array($req)) {
                echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
                $ip = $res['ip1'] == $res['ip2'] ? long2ip($res['ip1']) : long2ip($res['ip1']) . ' - ' . long2ip($res['ip2']);
                echo '<a href="index.php?act=sys_ipban&amp;mod=detail&amp;id=' . $res['id'] . '">' . $ip . '</a>';
                switch ($res['ban_type']) {
                    case 2:
                        echo ' Редирект';
                        break;

                    case 3:
                        echo ' Регистрация';
                        break;

                    default:
                        echo ' <b>Блокировка</b>';
                }
                echo '</div>';
                ++$i;
            }
        } else {
            echo '<div class="menu">Список пуст</div>';
        }
        echo '<div class="rmenu"><form action="index.php?act=sys_ipban&amp;mod=new" method="post"><input type="submit" name="" value="Банить IP" /></form></div>';
        echo '<div class="phdr">Всего: ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<p>' . pagenav('index.php?act=sys_ipban&amp;', $start, $total, $kmess) . '</p>';
            echo '<p><form action="index.php?act=sys_ipban" method="post"><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
        }
        echo '<p>';
        if ($total > 0)
            echo '<a href="index.php?act=sys_ipban&amp;mod=search">Поиск в базе</a><br /><a href="index.php?act=sys_ipban&amp;mod=clear">Разбанить все IP</a><br />';
        echo '<a href="index.php">Админ панель</a></p>';
}

?>