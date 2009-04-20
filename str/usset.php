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

$headmod = 'settings';
$textl = 'Настройки';
require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");

if ($user_id)
{
    switch ($act)
    {
        case "all":
            if (isset($_POST['submit']))
            {
                $sdvig = intval($_POST['sdvig']);
                if ($sdvig < -24)
                    $sdvig = -24;
                if ($sdvig > 24)
                    $sdvig = 24;
                $kolanywhwere = abs(intval($_POST['kolanywhwere']));
                if ($kolanywhwere < 5)
                    $kolanywhwere = 5;
                if ($kolanywhwere > 99)
                    $kolanywhwere = 99;
                $skin = check(trim($_POST['skin']));
                $arr = array();
                $dr = opendir('../theme');
                while ($skindef = readdir($dr))
                {
                    if (($skindef != ".") && ($skindef != ".."))
                    {
                        $arr[] = str_replace(".css", "", $skindef);
                    }
                }
                if (in_array($skin, $arr))
                {
                    mysql_query("UPDATE `users` SET
					`sdvig`='" . $sdvig . "',
					`kolanywhwere`='" . $kolanywhwere . "',
					`pereh`='" . intval($_POST['pereh']) . "',
					`offsm`='" . intval($_POST['offsm']) . "',
					`offtr`='" . intval($_POST['offtr']) . "',
					`digest`='" . intval($_POST['digest']) . "',
					`skin`='" . check(trim($_POST['skin'])) . "'
					WHERE id='" . $user_id . "';");
                    header("Location: usset.php?act=all&yes");
                } else
                {
                    header("Location: usset.php?act=all");
                }
            } else
            {
                echo '<form action="usset.php?act=all" method="post" >';
                echo '<div class="phdr"><b>Общие настройки</b></div>';
                if (isset($_GET['yes']))
                {
                    echo '<div class="rmenu">Настройки сохранены</div>';
                }
                echo '<div class="menu">Время: <b>' . date("H:i", $realtime + $sdvig * 3600) . '</b><br />';
				echo '<input type="text" name="sdvig" size="3" maxlength="3" value="' .
                    $datauser['sdvig'] . '"/> Сдвиг времени</div>';
                echo '<div class="menu"><input type="text" name="kolanywhwere" size="3" maxlength="3" value="' .
                    $datauser['kolanywhwere'] . '"/> Строк на страницу</div>';
                echo '<div class="menu">';
                echo '<input name="offsm" type="checkbox" value="1" ' . ($datauser['offsm'] ?
                    'checked="checked"' : '') . ' />&nbsp;Смайлы<br/>';
                echo '<input name="offtr" type="checkbox" value="1" ' . ($datauser['offtr'] ?
                    'checked="checked"' : '') . ' />&nbsp;Транслит<br/>';
                echo '<input name="pereh" type="checkbox" value="1" ' . ($datauser['pereh'] ?
                    'checked="checked"' : '') . ' />&nbsp;Быстрый переход<br/>';
                echo '<input name="digest" type="checkbox" value="1" ' . ($datauser['digest'] ?
                    'checked="checked"' : '') . ' />&nbsp;Дайджест';
                echo '</div>';
                echo '<div class="menu">Изменить скин<br/>';
                echo '<select name="skin">';
                $dr = opendir('../theme');
                while ($skindef = readdir($dr))
                {
                    if (($skindef != ".") && ($skindef != ".."))
                    {
                        $skindef = str_replace(".css", "", $skindef);
                        echo '<option' . ($skin == $skindef ? ' selected="selected">' : '>') . $skindef .
                            '</option>';
                    }
                }
                echo '</select></div>';
                echo '<div class="menu"><input type="submit" name="submit" value="Запомнить"/></div></form>';
                echo '<div class="bmenu"><a href="usset.php">Меню настроек</a></div>';
            }
            break;

        case "forum":
            if (isset($_POST['submit']))
            {
                $upfp = intval($_POST['upfp']);
                $farea = intval($_POST['farea']);
                mysql_query("UPDATE `users` SET 
				`farea`='" . $farea . "',
				`upfp`='" . $upfp . "'
				WHERE `id`='" . $user_id . "';");
                header("Location: usset.php?act=forum&yes");
            } else
            {
                echo '<div class="phdr"><b>Настройки Форума</b></div>';
                if (isset($_GET['yes']))
                {
                    echo '<div class="rmenu">Настройки форума сохранены</div>';
                }
                echo '<form action="usset.php?act=forum" method="post">';
                echo '<div class="menu">Новые посты:<br/>';
                if ($datauser['upfp'] == "0")
                {
                    echo "<input name='upfp' type='radio' value='0' checked='checked'/>";
                } else
                {
                    echo "<input name='upfp' type='radio' value='0' />";
                }
                echo " Внизу<br />";
                if ($datauser['upfp'] == "1")
                {
                    echo "<input name='upfp' type='radio' value='1' checked='checked' />";
                } else
                {
                    echo "<input name='upfp' type='radio' value='1'/>";
                }
                echo ' Вверху</div>';
                echo '<div class="menu">Поле ввода:<br/>';
                if ($datauser['farea'] == "1")
                {
                    echo "<input name='farea' type='radio' value='1' checked='checked'/>";
                } else
                {
                    echo "<input name='farea' type='radio' value='1' />";
                }
                echo " Вкл.<br />";
                if ($datauser['farea'] == "0")
                {
                    echo "<input name='farea' type='radio' value='0' checked='checked' />";
                } else
                {
                    echo "<input name='farea' type='radio' value='0'/>";
                }
                echo ' Выкл.</div>';
                echo '<div class="menu"><input type="submit" name="submit" value="Сохранить"/></div></form>';
                echo '<div class="gmenu"><a href="../forum">В форум</a></div>';
                echo '<div class="bmenu"><a href="usset.php">Меню настроек</a></div>';
            }
            break;

        case "chat":
            if (isset($_POST['submit']))
            {
                if ($dostsadm == 1)
                {
                    if (!empty($_POST['snas']))
                    {
                        $nastr = check(trim($_POST['snas']));
                    } else
                    {
                        $nastr = check(trim($_POST['nastr']));
                    }
                } else
                {
                    $nastr = check(trim($_POST['nastr']));
                }
                $refresh = intval($_POST['refresh']);
                $chmess = intval($_POST['chmess']);
                $charea = intval($_POST['charea']);
                if ($chmess < 5)
                {
                    $chmess = 5;
                }
                if ($chmess > 30)
                {
                    $chmess = 30;
                }
                if ($refresh < 15)
                {
                    $refresh = 15;
                }
                mysql_query("update `users` set chmes='" . $chmess . "',carea='" . $charea .
                    "',timererfesh='" . $refresh . "',nastroy='" . $nastr . "' where id='" . intval
                    ($_SESSION['uid']) . "';");
                header("Location: usset.php?act=chat&yes");
            } else
            {
                $nastr = array("без настроения", "бодрое", "прекрасное", "весёлое", "Унылое",
                    "ангельское", "агрессивное", "изумлённое", "удивленное", "злое", "сердитое",
                    "сонное", "озлобленное", "скучающее", "оживлённое", "угрюмое", "размышляющее",
                    "занятое", "нахальное", "холодное", "смущённое", "крутое", "смутённое",
                    "дьявольское", "сварливое", "счастливое", "горячее", "влюблённое", "невинное",
                    "вдохновлённое", "одинокое", "скрытое", "пушистое", "задумчивое",
                    "психоделическое", "расслабленое", "грустное", "испуганное", "шокированное",
                    "потрясенное", "больное", "хитрое", "усталое", "утомленное");
                echo '<div class="phdr"><b>Настройки чата</b></div>';
                if (isset($_GET['yes']))
                {
                    echo '<div class="rmenu">Ваши настройки чата изменены</div>';
                }
                echo '<form action="usset.php?act=chat" method="post">';
                echo '<div class="menu"><input type="text" name="refresh" size="3" maxlength="3" value="' .
                    $datauser['timererfesh'] . '"/> Обновление (сек.)</div>';
                echo '<div class="menu"><input type="text" name="chmess" size="3" maxlength="3" value="' .
                    $datauser['chmes'] . '"/> Постов на странице</div>';
                echo '<div class="menu">Поле ввода:<br/>';
                if ($datauser['carea'] == "1")
                {
                    echo "<input name='charea' type='radio' value='1' checked='checked'/>";
                } else
                {
                    echo "<input name='charea' type='radio' value='1' />";
                }
                echo ' Вкл.<br />';
                if ($datauser['carea'] == "0")
                {
                    echo "<input name='charea' type='radio' value='0' checked='checked' />";
                } else
                {
                    echo "<input name='charea' type='radio' value='0'/>";
                }
                echo ' Выкл.</div>';
                echo '<div class="menu">Выберите настроение:<br/><select name="nastr">';
                if (!empty($datauser['nastroy']))
                {
                    echo "<option>$datauser[nastroy]</option>";
                }
                foreach ($nastr as $v)
                {
                    if ($v != $datauser['nastroy'])
                    {
                        echo "<option>$v</option>";
                    }
                }
                echo '</select><br/>';
                if ($dostsadm == 1)
                {
                    if (in_array($datauser['nastroy'], $nastr))
                    {
                        $nastroy1 = "";
                    } else
                    {
                        $nastroy1 = $nastroy;
                    }
                    echo "Или укажите свое:<br/><input type='text' name='snas' value='" . $nastroy1 .
                        "'/><br/>";
                }
                echo '</div><div class="menu"><input type="submit" name="submit" value="Сохранить"/></div></form>';
                echo '<div class="gmenu"><a href="../chat">В чат</a></div>';
                echo '<div class="bmenu"><a href="usset.php">Меню настроек</a></div>';
            }
            break;

        default:
            echo '<div class="phdr"><b>Личные настройки</b></div>';
            echo '<div class="menu"><a href="usset.php?act=all">Общие</a><br /><small>Данные настройки влияют на весь сайт и его модули.</small></div>';
            echo '<div class="menu"><a href="usset.php?act=forum">Форум</a><br /><small>Настройка отображения информации на Форуме.</small></div>';
            echo '<div class="menu"><a href="usset.php?act=chat">Чат</a><br /><small>Индивидуальная настройка Чата.</small></div>';
            echo '<div class="bmenu"><a href="../index.php?mod=cab">В кабинет</a></div>';
            echo '<div class=""></div>';
            echo '<div class=""></div>';
            echo '<div class=""></div>';
            break;
    }
} else
{
    require_once ("../incfiles/head.php");
    print "Ошибка!<br/>";
}
require_once ("../incfiles/end.php");

?>