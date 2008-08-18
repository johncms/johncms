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

define('_IN_JOHNCMS', 1);

$textl = 'Управление';
require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");

if ($dostadm == 1)
{
    $do = isset($_GET['do']) ? $_GET['do'] : '';
    switch ($do)
    {
        case 'new';
            ////////////////////////////////////////////////////////////
            // Форма ввода IP адреса для Бана                         //
            ////////////////////////////////////////////////////////////
            echo '<div class="phdr">Баним IP</div>';
            echo '<p><form action="ipban.php?do=insert" method="post">';
            echo 'Введите IP адрес:<br />';
            echo '<input type="text" name="ip"/><br/><br/>';
            echo '<b>Вид блокировки:</b><br />';
            echo '<input name="term" type="radio" value="1" checked="checked" />Закрыть доступ на сайт<br />';
            echo '<input name="term" type="radio" value="3" />Закрыть регистрацию<br />';
            echo '<input name="term" type="radio" value="2" />Редирект<br /><br />';
            echo 'Для "Редиректа", при желании, можно указать адрес URL, на который будет отсылаться забаненный адрес.<br />';
            echo 'Если поле не заполнить, то редиректом отправятся "Ф ГазенвагенЪ".<br />';
            echo 'Адрес вводите в формате http://url.com<br /><br />';
            echo '<b>URL редиректа:</b><br /><small>Необязательное поле</small><br />';
            echo '<input type="text" name="url"/><br/><br/>';
            echo '<b>Причина бана:</b><br /><small>Необязательное поле</small><br />';
            echo '<textarea cols="20" rows="4" name="reason"></textarea><br /><br />';
            echo '<input type="submit" name="submit" value="Сохранить"/>';
            echo '</form></p>';
            echo '<hr /><p><a href="ipban.php">Отмена</a><br /><a href="main.php">В Админку</a></p>';
            break;

        case 'insert':
            ////////////////////////////////////////////////////////////
            // Проверяем адрес и вставляем в базу                     //
            ////////////////////////////////////////////////////////////
            $ban_ip = ip2long($_POST['ip']);
            $ban_term = intval($_POST['term']);
            $ban_url = $_POST['url'];
            $reason = $_POST['reason'];
            if (empty($ban_ip))
            {
                echo '<p><b>ОШИБКА!</b><br />Адрес IP введен неправильно.</p>';
                echo '<p><a href="ipban.php?do=new">Назад</a><br /><a href="main.php">В Админку</a></p>';
                require_once ("../incfiles/end.php");
                exit;
            }
            if (($ban_ip == $ipl && $ban_term == 1) || ($ban_ip == $ipl && $ban_term == 2))
            {
                echo '<p><b>ОШИБКА!</b><br />Вы пытаетесь отправить в бан собственный адрес IP.</p>';
                echo '<p><a href="ipban.php?do=new">Назад</a><br /><a href="main.php">В Админку</a></p>';
                require_once ("../incfiles/end.php");
                exit;
            }
            $req = mysql_query("SELECT * FROM `cms_ban_ip` WHERE `ip`='" . $ban_ip . "'");
            if (mysql_num_rows($req) != 0)
            {
                echo '<p><b>ОШИБКА!</b><br />Адрес <b>' . long2ip($ban_ip) . '</b> уже есть в базе.</p>';
                echo '<p><a href="ipban.php?do=new">Назад</a><br /><a href="main.php">В Админку</a></p>';
                require_once ("../incfiles/end.php");
                exit;
            }
            mysql_query("INSERT INTO `cms_ban_ip` SET
			`ip`='" . $ban_ip . "',
			`ban_type`='" . $ban_term . "',
			`link`='" . check($ban_url) . "',
			`who`='" . $login . "',
			`reason`='" . check($reason) . "',
			`date`='" . $realtime . "';");
            echo '<p>IP адрес: ' . long2ip($ban_ip) . '<br />был успешно добавлен в базу.</p>';
            echo '<hr /><p><a href="ipban.php">Продолжить</a><br /><a href="main.php">В Админку</a></p>';
            break;

        case 'clear':
            ////////////////////////////////////////////////////////////
            // Очистка таблицы банов по IP                            //
            ////////////////////////////////////////////////////////////
            if (isset($_GET['yes']))
            {
                mysql_query("TRUNCATE TABLE `cms_ban_ip`;");
                echo '<p>Таблица IP банов успешно очищена.<br />Разбанены все адреса.</p>';
                echo '<p><a href="ipban.php">Продолжить</a><br /><a href="main.php">В Админку</a></p>';
            } else
            {
                echo '<p><b>ВНИМАНИЕ!</b><br />Таблица IP банов будет очищена.<br />Вы действительно хотите разбанить ВСЕ адреса IP?</p>';
                echo '<p><a href="ipban.php">Отмена</a><br /><a href="ipban.php?do=clear&amp;yes=yes">Да, разбанить</a></p><p><a href="main.php">В Админку</a></p>';
            }
            break;

        case 'detail':
            ////////////////////////////////////////////////////////////
            // Выводим подробности запрошенного IP                    //
            ////////////////////////////////////////////////////////////
            echo '<div class="phdr">Блокированный IP</div>';
            if (isset($_GET['ip']))
            {
                $baseip = intval($_GET['ip']);
            } elseif (isset($_POST['ip']))
            {
                $baseip = ip2long($_POST['ip']);
            } else
            {
                echo '<p>Такого адреса нет в базе.</p>';
                echo '<p><a href="ipban.php">Назад</a><br /><a href="main.php">В Админку</a></p>';
                require_once ("../incfiles/end.php");
                exit;
            }
            $req = mysql_query("SELECT * FROM `cms_ban_ip` WHERE `ip`='" . $baseip . "' LIMIT 1;");
            if (mysql_num_rows($req) != 1)
            {
                echo '<p>Такого адреса нет в базе.</p>';
                echo '<p><a href="ipban.php">Назад</a><br /><a href="main.php">В Админку</a></p>';
                require_once ("../incfiles/end.php");
                exit;
            } else
            {
                $res = mysql_fetch_array($req);
                echo '<p>Адрес IP: <b>' . long2ip($res['ip']) . '</b></p>';
                echo '<p>Банил: <b>' . $res['who'] . '</b><br />';
                echo 'Дата: <b>' . date('d.m.Y', $res['date']) . '</b><br />';
                echo 'Время: <b>' . date('H:i:s', $res['date']) . '</b></p>';
                echo '<p>Тип бана: ';
                switch ($res['ban_type'])
                {
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
                echo '<br />Причина: ' . $res['reason'];
                echo '</p><hr /><p><a href="ipban.php?do=edit&amp;ip=' . $res['ip'] . '">Изменить</a><br /><a href="ipban.php?do=del&amp;ip=' . $res['ip'] . '">Разбанить</a></p><p><a href="main.php">В Админку</a></p>';
            }
            break;

        case 'edit':
            ////////////////////////////////////////////////////////////
            // Редактируем детали запрошенного IP                     //
            ////////////////////////////////////////////////////////////
            if (isset($_GET['ip']))
            {
                $baseip = intval($_GET['ip']);
            } else
            {
                echo '<p>Такого адреса нет в базе.</p>';
                echo '<p><a href="ipban.php">Назад</a><br /><a href="main.php">В Админку</a></p>';
                require_once ("../incfiles/end.php");
                exit;
            }
            $req = mysql_query("SELECT * FROM `cms_ban_ip` WHERE `ip`='" . $baseip . "' LIMIT 1;");
            if (mysql_num_rows($req) != 1)
            {
                echo '<p>Такого адреса нет в базе.</p>';
                echo '<p><a href="ipban.php">Назад</a><br /><a href="main.php">В Админку</a></p>';
                require_once ("../incfiles/end.php");
                exit;
            } else
            {
                if (isset($_POST['submit']))
                {
                    mysql_query("UPDATE `cms_ban_ip` SET
					`ban_type`='" . intval($_POST['term']) . "',
					`link`='" . check($_POST['url']) . "',
					`who`='" . $login . "',
					`reason`='" . check($_POST['reason']) . "',
					`date`='" . $realtime . "'
					WHERE `ip`='" . $baseip . "' LIMIT 1;");
                    echo '<p>Изменения успешно сохранены.</p>';
                    echo '<p><a href="ipban.php?do=detail&amp;ip=' . $baseip . '">Продолжить</a><br /><a href="main.php">В Админку</a></p>';
                } else
                {
                    $res = mysql_fetch_array($req);
                    echo '<p>';
                    echo 'Адрес IP: <b>' . long2ip($res['ip']) . '</b></p>';
                    echo '<p><form action="ipban.php?do=edit&amp;ip=' . $baseip . '" method="post">';
                    echo '<b>Тип бана:</b><br />';
                    echo '<input name="term" type="radio" value="1" ';
                    if ($res['ban_type'] == 1)
                        echo 'checked="checked" ';
                    echo '/>Закрыть доступ на сайт<br />';
                    echo '<input name="term" type="radio" value="3" ';
                    if ($res['ban_type'] == 3)
                        echo 'checked="checked" ';
                    echo '/>Закрыть регистрацию<br />';
                    echo '<input name="term" type="radio" value="2" ';
                    if ($res['ban_type'] == 2)
                        echo 'checked="checked" ';
                    echo '/>Редирект<br /><br />';
                    echo 'Для "Редиректа", при желании, можно указать адрес URL, на который будет отсылаться забаненный адрес.<br />';
                    echo 'Если поле не заполнить, то редиректом отправятся "Ф ГазенвагенЪ".<br />';
                    echo 'Адрес вводите в формате http://url.com<br /><br />';
                    echo '<b>URL редиректа:</b><br /><small>Необязательное поле</small><br />';
                    echo '<input type="text" name="url" value="' . $res['link'] . '" /><br/><br/>';
                    echo '<b>Причина бана:</b><br /><small>Необязательное поле</small><br />';
                    echo '<textarea cols="20" rows="4" name="reason">' . $res['reason'] . '</textarea><br /><br />';
                    echo '<input type="submit" name="submit" value="Сохранить"/>';
                    echo '</form></p>';
                    echo '<p><a href="ipban.php?do=detail&amp;ip=' . $baseip . '">Отмена</a><br /><a href="main.php">В Админку</a></p>';
                }
            }
            break;

        case 'del':
            ////////////////////////////////////////////////////////////
            // Удаление выбранного IP из базы                         //
            ////////////////////////////////////////////////////////////
            if (isset($_GET['ip']))
            {
                $baseip = intval($_GET['ip']);
            } else
            {
                echo '<p>Такого адреса нет в базе.</p>';
                echo '<p><a href="ipban.php">К списку IP</a><br /><a href="main.php">В Админку</a></p>';
                require_once ("../incfiles/end.php");
                exit;
            }
            if (isset($_GET['yes']))
            {
                mysql_query("DELETE FROM `cms_ban_ip` WHERE `ip`='" . $baseip . "' LIMIT 1;");
                mysql_query("OPTIMIZE TABLE `cms_ban_ip`;");
                echo '<p>Адрес ' . long2ip($baseip) . ' разбанен.</p>';
                echo '<p><a href="ipban.php">Назад</a><br /><a href="main.php">В Админку</a></p>';
            } else
            {
                $req = mysql_query("SELECT * FROM `cms_ban_ip` WHERE `ip`='" . $baseip . "' LIMIT 1;");
                if (mysql_num_rows($req) != 1)
                {
                    echo '<p>Такого адреса нет в базе.</p>';
                    echo '<p><a href="ipban.php">Назад</a><br /><a href="main.php">В Админку</a></p>';
                    require_once ("../incfiles/end.php");
                    exit;
                } else
                {
                    echo '<p>Адрес <b>' . long2ip($baseip) . '</b> будет удален из базы.<br />Вы действительно хотите разбанить данный адрес?</p>';
                    echo '<p><a href="ipban.php?do=del&amp;ip=' . $baseip . '&amp;yes=yes">Разбанить</a><br /><a href="ipban.php?do=detail&amp;ip=' . $baseip . '">Отмена</a></p><p><a href="main.php">В Админку</a></p>';
                }
            }
            break;

        case 'search':
            ////////////////////////////////////////////////////////////
            // Форма поиска забаненного IP                            //
            ////////////////////////////////////////////////////////////
            echo "<p><b>Админ Панель</b><br />Поиск блокированного адреса IP</p><hr />";
            echo '<p><form action="ipban.php?do=detail" method="post">';
            echo '<b>Введите IP адрес:</b><br />';
            echo '<input type="text" name="ip"/><br/><br/>';
            echo '<input type="submit" name="submit" value="Поиск"/>';
            echo '</form></p>';
            echo '<hr /><p><a href="ipban.php">Отмена</a><br /><a href="main.php">В Админку</a></p>';
            break;

        default:
            ////////////////////////////////////////////////////////////
            // Вывод общего списка забаненных IP                      //
            ////////////////////////////////////////////////////////////
            echo '<div class="phdr">Бан по IP</div>';
            $req = mysql_query("SELECT * FROM `cms_ban_ip` ORDER BY `ip` ASC;");
            $total = @mysql_num_rows($req);
            $page = (isset($_GET['page']) && ($_GET['page'] > 0)) ? intval($_GET['page']):
            1;
            $start = $page * $kmess - $kmess;
            if ($total < $start + $kmess)
            {
                $end = $total;
            } else
            {
                $end = $start + $kmess;
            }
            if ($total != 0)
            {
                while ($res = mysql_fetch_array($req))
                {
                    if ($i >= $start && $i < $end)
                    {
                        $d = $i / 2;
                        $d1 = ceil($d);
                        $d2 = $d1 - $d;
                        $d3 = ceil($d2);
                        if ($d3 == 0)
                        {
                            echo '<div class="c">';
                        } else
                        {
                            echo '<div class="b">';
                        }
                        echo '<a href="ipban.php?do=detail&amp;ip=' . $res['ip'] . '">' . long2ip($res['ip']) . '</a>';
                        switch ($res['ban_type'])
                        {
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
                    }
                    ++$i;
                }
                if ($total > $kmess)
                {
                    echo '<p>';
					$pagenav = array('address' => 'ipban.php?', 'total' => $total, 'numpr' => $kmess, 'page' => $page);
                    pagenav($pagenav);
                    echo '</p>';
                }
            } else
            {
                echo "Список пуст.";
            }
            echo '<p><a href="ipban.php?do=new">Банить IP</a><br />';
            if ($total > 0)
                echo '<a href="ipban.php?do=search">Поиск в базе</a><br /><a href="ipban.php?do=clear">Разбанить все IP</a>';
            echo '</p><p><a href="main.php">В Админку</a></p>';
    }
} else
{
    header("Location: ../index.php?err");
}
require_once ("../incfiles/end.php");

?>