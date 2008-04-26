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

if (!empty($_SESSION['uid']))
{
    $q = mysql_query("select * from `users` where id='" . intval(check($_SESSION['uid'])) . "';");
    $datauser = mysql_fetch_array($q);
    if (!empty($_GET['act']))
    {
        $act = check($_GET['act']);
    }
    $arrcol = array("#000000" => "Чёрный", "#000033" => "Тёмно-синий", "#000099" => "Синий", "#0000FF" => "Светло-синий", "#0099FF" => "Голубой", "#99CCFF" => "Светло-голубой", "#00CCCC" => "Аквамарин", "#660066" => "Фиолетовый", "#660000" =>
        "Бордовый", "#990000" => "Тёмно-красный", "#CC0000" => "Красный", "#FF0000" => "Ярко-красный", "#FF0033" => "Алый", "#FF9900" => "Оранжевый", "#FFCC00" => "Золотой", "#FFFF00" => "Жёлтый", "#FFFFCC" => "Светло-жёлтый", "#003300" =>
        "Тёмно-зелёный", "#006600" => "Зелёный", "#009900" => "Ярко-зелёный", "#99FF99" => "Светло-зелёный", "#99FF33" => "Салатовый", "#009999" => "Бирюзовый", "#660033" => "Лиловый", "#990033" => "Малиновый", "#CC33CC" => "Сиреневый", "#FF0066" =>
        "Розовый", "#FF6666" => "Коралл", "#FF9999" => "Светлый коралл", "#663300" => "Тёмно-коричневый", "#666600" => "Коричневый", "#CC6600" => "Светло-коричневый", "#996600" => "Горчичный", "#333333" => "Тёмно-серый", "#666666" => "Серый",
        "#999999" => "Светло-серый", "#CCCCCC" => "Серебряный", "#FFFFFF" => "Белый");
    $num = array_keys($arrcol);
    foreach ($num as $i => $color)
    {
        if ($i >= 0 && $i < 4 || $i > 6 && $i < 13 || $i == 17 || $i == 18 || $i > 21 && $i <= 33)
        {
            $numb[] = $color;
        }
    }
    switch ($act)
    {
            ######
        case "help":
            require_once ("../incfiles/head.php");
            echo "Справка по цветам<br/>";
            foreach ($arrcol as $k => $v)
            {
                if (in_array($k, $numb))
                {
                    $cl = "#FFFFFF";
                } else
                {
                    $cl = "#000000";
                }
                echo "<div style='background:" . $k . ";color:" . $cl . ";'>$v</div>";
            }
            echo "<hr/><a href='usset.php?act=color'>Назад</a><br/>";
            break;

        case "yes":
            $pereh = intval(check(trim($_POST['pereh'])));
            $offgr = intval(check(trim($_POST['offgr'])));
            $offsm = intval(check(trim($_POST['offsm'])));
            $offtr = intval(check(trim($_POST['offtr'])));
            $offpg = intval(check(trim($_POST['offpg'])));
            $sdvig = intval(check(trim($_POST['sdvig'])));
            mysql_query("update `users` set  sdvig='" . $sdvig . "',offgr='" . $offgr . "',pereh='" . $pereh . "',offsm='" . $offsm . "',offtr='" . $offtr . "',offpg='" . $offpg . "' where id='" . intval($_SESSION['uid']) . "';");
            header("Location: usset.php?yes");
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
                $refresh = intval(check(trim($_POST['refresh'])));
                $chmess = intval(check(trim($_POST['chmess'])));
                $charea = intval(check(trim($_POST['charea'])));
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

                mysql_query("update `users` set chmes='" . $chmess . "',carea='" . $charea . "',timererfesh='" . $refresh . "',nastroy='" . $nastr . "' where id='" . intval($_SESSION['uid']) . "';");
                header("Location: usset.php?act=chat&yes");


            } else
            {
                require_once ("../incfiles/head.php");
                if (isset($_GET['yes']))
                {
                    echo "Ваши настройки чата изменены!<br/>";
                }
                $nastr = array("без настроения", "бодрое", "прекрасное", "весёлое", "Унылое", "ангельское", "агрессивное", "изумлённое", "удивленное", "злое", "сердитое", "сонное", "озлобленное", "скучающее", "оживлённое", "угрюмое", "размышляющее",
                    "занятое", "нахальное", "холодное", "смущённое", "крутое", "смутённое", "дьявольское", "сварливое", "счастливое", "горячее", "влюблённое", "невинное", "вдохновлённое", "одинокое", "скрытое", "пушистое", "задумчивое", "психоделическое",
                    "расслабленое", "грустное", "испуганное", "шокированное", "потрясенное", "больное", "хитрое", "усталое", "утомленное");
                echo "<form action='usset.php?act=chat' method='post' >Настройки чата<br/>Время обновления в чате<br/><input type='text' name='refresh' value='" . $datauser[timererfesh] .
                    "'/><br/>Количество постов на страницу:<br/><input type='text' name='chmess' value='" . $datauser[chmes] . "'/><br/>";
                echo "Поле ввода:<br/>Вкл.&nbsp;&nbsp;";
                if ($datauser[carea] == "1")
                {
                    echo "<input name='charea' type='radio' value='1' checked='checked'/>";
                } else
                {
                    echo "<input name='charea' type='radio' value='1' />";
                }
                echo " &nbsp; &nbsp; ";
                if ($datauser[carea] == "0")
                {
                    echo "<input name='charea' type='radio' value='0' checked='checked' />";
                } else
                {
                    echo "<input name='charea' type='radio' value='0'/>";
                }
                echo "Выкл.<br/>";
                echo "Настроение:<br/><select name='nastr'>";
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
                echo "</select><br/>";
                if ($dostsadm == 1)
                {
                    if (in_array($datauser['nastroy'], $nastr))
                    {
                        $nastroy1 = "";
                    } else
                    {
                        $nastroy1 = $nastroy;
                    }
                    echo "Иное настроение(имеет больший приоритет!):<br/><input type='text' name='snas' value='" . $nastroy1 . "'/><br/>";
                }
                echo "<input type='submit' name='submit' value='ok'/></form><br/>";

                echo "<a href='usset.php'>Общие настройки</a><br/>";
                echo "<a href='../chat/?'>В чат</a><br/>";
            }

            break;

            ###############
        case "forum":

            $nmen = array(1 => "Имя", "Город", "Инфа", "ICQ", "E-mail", "Мобила", "Дата рождения", "Сайт");
            if (isset($_POST['submit']))
            {

                $kolmess = intval(check(trim($_POST['kolmess'])));
                if ($kolmess < 5)
                {
                    $kolmess = 5;
                }
                if ($kolmess > 30)
                {
                    $kolmess = 30;
                }
                $upfp = intval(check(trim($_POST['upfp'])));
                $farea = intval(check(trim($_POST['farea'])));

                foreach ($_POST['nmenu'] as $value)
                {
                    $nmenu1[] = intval(check(trim($value)));
                }
                $nmenu = implode(",", $nmenu1);

                mysql_query("update `users` set nmenu='" . $nmenu . "',farea='" . $farea . "',upfp='" . $upfp . "',kolanywhwere='" . $kolmess . "' where id='" . intval($_SESSION['uid']) . "';");
                header("Location: usset.php?act=forum&yes");


            } else
            {
                require_once ("../incfiles/head.php");
                if (isset($_GET['yes']))
                {
                    echo "Ваши настройки форума изменены!<br/>";
                }
                echo "<form action='usset.php?act=forum' method='post' >Настройки форума<br/>Количество постов и тем на страницу:<br/><input type='text' name='kolmess' value='" . $datauser[kolanywhwere] . "'/><br/>";
                echo "Новые посты:<br/>Внизу";
                if ($datauser[upfp] == "0")
                {
                    echo "<input name='upfp' type='radio' value='0' checked='checked'/>";
                } else
                {
                    echo "<input name='upfp' type='radio' value='0' />";
                }
                echo " &nbsp; &nbsp; ";
                if ($datauser[upfp] == "1")
                {
                    echo "<input name='upfp' type='radio' value='1' checked='checked' />";
                } else
                {
                    echo "<input name='upfp' type='radio' value='1'/>";
                }
                echo "Вверху<br/>";
                echo "Поле ввода:<br/>Вкл.&nbsp;&nbsp;";
                if ($datauser[farea] == "1")
                {
                    echo "<input name='farea' type='radio' value='1' checked='checked'/>";
                } else
                {
                    echo "<input name='farea' type='radio' value='1' />";
                }
                echo " &nbsp; &nbsp; ";
                if ($datauser[farea] == "0")
                {
                    echo "<input name='farea' type='radio' value='0' checked='checked' />";
                } else
                {
                    echo "<input name='farea' type='radio' value='0'/>";
                }
                echo "Выкл.<br/>";

                echo "Ник-меню:<br/>";
                if (!empty($datauser[nmenu]))
                {
                    $nmenu1 = explode(",", $datauser[nmenu]);
                }

                foreach ($nmen as $k => $v)
                {
                    if (in_array($k, $nmenu1))
                    {
                        echo "<input type='checkbox' name='nmenu[]' value='" . $k . "' checked='checked'/>$v<br/>";
                    } else
                    {
                        echo "<input type='checkbox' name='nmenu[]' value='" . $k . "'/>$v<br/>";
                    }
                }


                echo "<input type='submit' name='submit' value='ok'/></form><br/>";


                echo "<a href='usset.php'>Общие настройки</a><br/>";
                echo "<a href='../forum/?'>В форум</a><br/>";
            }
            break;
            #####################
        case "view":
            if (empty($_GET['id']))
            {
                require_once ("../incfiles/head.php");
                echo "Ошибка!<br/>";
                exit;
            }
            $id = intval(check($_GET['id']));
            $th = mysql_query("select * from `themes` where id='" . $id . "';");
            $thm = mysql_fetch_array($th);
            if (isset($_POST['submit']))
            {
                mysql_query("update `users` set pfon='" . $thm[pfon] . "',cpfon='" . $thm[cpfon] . "',ccfon='" . $thm[ccfon] . "',cctx='" . $thm[cctx] . "',bgcolor='" . $thm[bgcolor] . "',tex='" . $thm[tex] . "',link='" . $thm[link] . "',bclass='" . $thm[bclass] .
                    "',cntem='" . $thm[cntem] . "',ccolp='" . $thm[ccolp] . "',cdtim='" . $thm[cdtim] . "',cssip='" . $thm[cssip] . "',csnik='" . $thm[csnik] . "',cclass='" . $thm[cclass] . "',conik='" . $thm[conik] . "',cadms='" . $thm[cadms] . "',cons='" . $thm[cons] .
                    "',coffs='" . $thm[coffs] . "',cdinf='" . $thm[cdinf] . "' where id='" . intval(check($_SESSION['uid'])) . "';");
                header("Location: usset.php?act=color&yes");
            } else
            {
                require_once ("../incfiles/head.php");
                echo "<div style='background:" . $thm[bgcolor] . ";'><font color='" . $thm[tex] . "'>Внешний вид раздела форума</font><br/>";

                echo "<font color='" . $thm[cntem] . "'><b>Название раздела</b></font><br/><font color='" . $thm[link] . "'>Ссылка &quot; Новая тема&quot;</font><br/>";

                echo "<div style='background:" . $thm[bclass] . ";'><img src='../images/op.gif' alt=''/><font color='" . $thm[cntem] . "'>Название темы</font><font color='" . $thm[ccolp] . "'> [Кол-во постов]</font><br/><font color='" . $thm[cdtim] .
                    "'>(Дата и время)</font><br/><font color='" . $thm[cssip] . "'>[Автор темы и посл. поста]</font></div><div style='background:" . $thm[cclass] . ";'><img src='../images/np.gif' alt=''/><font color='" . $thm[cntem] .
                    "'>Название темы</font><font color='" . $thm[ccolp] . "'> [Кол-во постов]</font><br/><font color='" . $thm[cdtim] . "'>(Дата и время)</font><br/><font color='" . $thm[cssip] . "'>[Автор темы и посл. поста]</font></div><hr/>";
                echo "<font color='" . $thm[tex] . "'>Внешний вид темы</font><br/>";

                echo "<font color='" . $thm[cntem] . "'><b>Название темы</b></font><br/><font color='" . $thm[ccolp] . "'>Кол-во постов</font><br/><font color='" . $thm[link] . "'>Ссылка &quot; Вниз&quot;</font><br/>";

                echo "<div style='background:" . $thm[bclass] . ";'>";
                if ($thm[pfon] == 1)
                {
                    echo "<div style='background:" . $thm[cpfon] . ";'>";
                }

                echo "<img src='../images/m.gif' alt=''/><b><font color='" . $thm[csnik] . "'>Свой ник</font></b><font color='" . $thm[cadms] . "'> Адмстатус </font><font color='" . $thm[cons] . "'> [ON]</font><font color='" . $thm[cdtim] .
                    "'>(Время поста)</font><br/>";
                if ($thm[pfon] == 1)
                {
                    echo "</div>";
                }

                echo "<font color='" . $thm[tex] . "'>Текст</font><br/><font color='" . $thm[cdinf] . "'>Доп. информация</font><br/></div><div style='background:" . $thm[cclass] . ";'>";

                if ($thm[pfon] == 1)
                {
                    echo "<div style='background:" . $thm[cpfon] . ";'>";
                }

                echo "<img src='../images/f.gif' alt=''/><b><font color='" . $thm[conik] . "'>Чужой ник</font></b> <font color='" . $thm[conik] . "'> [ц]</font><font color='" . $thm[coffs] . "'> [Off]</font><font color='" . $thm[cdtim] .
                    "'>(Время поста)</font><br/>";
                if ($thm[pfon] == 1)
                {
                    echo "</div>";
                }

                echo "<div style='background:" . $thm[ccfon] . ";'><font color='" . $thm[cctx] . "'>Цитата</font><br/></div><font color='" . $thm[tex] . "'>Текст</font><br/><font color='" . $thm[cdinf] . "'>Доп. информация</font><br/></div>";
                echo "<form action='usset.php?act=view&amp;id=" . $id . "' method='post'><input type='submit' name='submit' value='Установить'/></form><br/>";
                echo "</div>";
                echo "<a href='usset.php?act=other'>Готовые схемы</a><br/>";
                echo "<a href='usset.php'>Общие настройки</a><br/>";
            }

            break;


            ####################
        case "other":
            require_once ("../incfiles/head.php");
            $q3 = mysql_query("select * from `themes`;");
            $count = mysql_num_rows($q3);
            if (empty($_GET['page']))
            {
                $page = 1;
            } else
            {
                $page = intval($_GET['page']);
            }
            $start = $page * 10 - 10;
            if ($count < $start + 10)
            {
                $end = $count;
            } else
            {
                $end = $start + 10;
            }
            $i = 0;
            while ($arr = mysql_fetch_array($q3))
            {
                if ($i >= $start && $i < $end)
                {
                    $vr = date("d.m.y", $arr[time]);
                    echo "<a href='usset.php?act=view&amp;id=" . $arr[id] . "'>$arr[name]($vr)</a><br/>";
                }
                ++$i;
            }
            if ($count > 10)
            {
                echo "<hr/>";

                $ba = ceil($count / 10);
                if ($offpg != 1)
                {
                    echo "Страницы:<br/>";
                } else
                {
                    echo "Страниц: $ba<br/>";
                }
                $asd = $start - (10);
                $asd2 = $start + (10 * 2);

                if ($start != 0)
                {
                    echo '<a href="usset.php?act=other&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
                }
                if ($offpg != 1)
                {
                    if ($asd < $count && $asd > 0)
                    {
                        echo ' <a href="usset.php?act=other&amp;page=1&amp;">1</a> .. ';
                    }
                    $page2 = $ba - $page;
                    $pa = ceil($page / 2);
                    $paa = ceil($page / 3);
                    $pa2 = $page + floor($page2 / 2);
                    $paa2 = $page + floor($page2 / 3);
                    $paa3 = $page + (floor($page2 / 3) * 2);
                    if ($page > 13)
                    {
                        echo ' <a href="usset.php?act=other&amp;page=' . $paa . '">' . $paa . '</a> <a href="usset.php?act=other&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="usset.php?act=other&amp;page=' . ($paa * 2) . '">' . ($paa * 2) .
                            '</a> <a href="users.php?page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
                    } elseif ($page > 7)
                    {
                        echo ' <a href="usset.php?act=other&amp;page=' . $pa . '">' . $pa . '</a> <a href="usset.php?act=other&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
                    }
                    for ($i = $asd; $i < $asd2; )
                    {
                        if ($i < $count && $i >= 0)
                        {
                            $ii = floor(1 + $i / 10);

                            if ($start == $i)
                            {
                                echo " <b>$ii</b>";
                            } else
                            {
                                echo ' <a href="usset.php?act=other&amp;page=' . $ii . '">' . $ii . '</a> ';
                            }
                        }
                        $i = $i + 10;
                    }
                    if ($page2 > 12)
                    {
                        echo ' .. <a href="usset.php?act=other&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="usset.php?act=other&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="usset.php?act=other&amp;page=' . ($paa3) . '">' . ($paa3) .
                            '</a> <a href="usset.php?act=other&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
                    } elseif ($page2 > 6)
                    {
                        echo ' .. <a href="usset.php?act=other&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="usset.php?act=other&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
                    }
                    if ($asd2 < $count)
                    {
                        echo ' .. <a href="usset.php?act=other&amp;page=' . $ba . '">' . $ba . '</a>';
                    }
                } else
                {
                    echo "<b>[$page]</b>";
                }


                if ($count > $start + 10)
                {
                    echo ' <a href="usset.php?act=other&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
                }
                echo "<form action='usset.php'>Перейти к странице:<br/><input type='text' name='page' title='Введите номер страницы'/><input type='hidden' name='act' value='other'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
            }

            echo "<hr/><div>Всего: $count</div>";

            break;

        default:
            require_once ("../incfiles/head.php");
            if (isset($_GET['yes']))
            {
                echo "Ваши настройки изменены!<br/>";
            }
            echo "<form action='usset.php?act=yes' method='post' >Общие настройки<br/>Временной сдвиг:<br/><input type='text' name='sdvig' value='" . $datauser[sdvig] . "'/><br/>";
            echo "Графика:<br/>Вкл.";
            if ($offgr == "0")
            {
                echo "<input name='offgr' type='radio' value='0' checked='checked'/>";
            } else
            {
                echo "<input name='offgr' type='radio' value='0' />";
            }
            echo " &nbsp; &nbsp; ";
            if ($offgr == "1")
            {
                echo "<input name='offgr' type='radio' value='1' checked='checked' />";
            } else
            {
                echo "<input name='offgr' type='radio' value='1'/>";
            }
            echo "Выкл<br/>";

            echo "Смайлы:<br/>Вкл.";
            if ($offsm == "0")
            {
                echo "<input name='offsm' type='radio' value='0' checked='checked'/>";
            } else
            {
                echo "<input name='offsm' type='radio' value='0' />";
            }
            echo " &nbsp; &nbsp; ";
            if ($offsm == "1")
            {
                echo "<input name='offsm' type='radio' value='1' checked='checked' />";
            } else
            {
                echo "<input name='offsm' type='radio' value='1'/>";
            }
            echo "Выкл<br/>";

            echo "Постраничный вывод:<br/>Вкл.";
            if ($offpg == "0")
            {
                echo "<input name='offpg' type='radio' value='0' checked='checked'/>";
            } else
            {
                echo "<input name='offpg' type='radio' value='0' />";
            }
            echo " &nbsp; &nbsp; ";
            if ($offpg == "1")
            {
                echo "<input name='offpg' type='radio' value='1' checked='checked' />";
            } else
            {
                echo "<input name='offpg' type='radio' value='1'/>";
            }
            echo "Выкл<br/>";

            echo "Выбор транслита:<br/>Вкл.";
            if ($offtr == "0")
            {
                echo "<input name='offtr' type='radio' value='0' checked='checked'/>";
            } else
            {
                echo "<input name='offtr' type='radio' value='0' />";
            }
            echo " &nbsp; &nbsp; ";
            if ($offtr == "1")
            {
                echo "<input name='offtr' type='radio' value='1' checked='checked' />";
            } else
            {
                echo "<input name='offtr' type='radio' value='1'/>";
            }
            echo "Выкл<br/>";

            echo "Быстрый переход:<br/>Вкл.";
            if ($datauser['pereh'] == "0")
            {
                echo "<input name='pereh' type='radio' value='0' checked='checked'/>";
            } else
            {
                echo "<input name='pereh' type='radio' value='0' />";
            }
            echo " &nbsp; &nbsp; ";
            if ($datauser['pereh'] == "1")
            {
                echo "<input name='pereh' type='radio' value='1' checked='checked' />";
            } else
            {
                echo "<input name='pereh' type='radio' value='1'/>";
            }
            echo "Выкл<br/>";

            echo "<input type='submit' value='ok'/></form><br/>";
            echo "<a href='usset.php?act=forum'>Настройки форума</a><br/>";
            echo "<a href='usset.php?act=chat'>Настройки чата</a><br/><br/>";
            break;
    }
} else
{
    require_once ("../incfiles/head.php");
    print "Ошибка!<br/>";
}
require_once ("../incfiles/end.php");
?>