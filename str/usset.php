<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.0.0 RC2                                                        //
// Дата релиза: 08.02.2008                                                    //
// Авторский сайт: http://gazenwagen.com                                      //
////////////////////////////////////////////////////////////////////////////////
// Оригинальная идея и код: Евгений Рябинин aka JOHN77                        //
// E-mail: 
// Модификация, оптимизация и дизайн: Олег Касьянов aka AlkatraZ              //
// E-mail: alkatraz@batumi.biz                                                //
// Плагиат и удаление копирайтов заруганы на ближайших родственников!!!       //
////////////////////////////////////////////////////////////////////////////////
// Внимание!                                                                  //
// Авторские версии данных скриптов публикуются ИСКЛЮЧИТЕЛЬНО на сайте        //
// http://gazenwagen.com                                                      //
// Если Вы скачали данный скрипт с другого сайта, то его работа не            //
// гарантируется и поддержка не оказывается.                                  //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_PUSTO', 1);

$headmod = 'settings';
$textl = 'Настройки';
require ("../incfiles/db.php");
require ("../incfiles/func.php");
require ("../incfiles/data.php");


if (!empty($_SESSION['pid']))
{
    $q = mysql_query("select * from `users` where id='" . intval(check($_SESSION['pid'])) . "';");
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
            require ("../incfiles/head.php");
            require ("../incfiles/inc.php");
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
            #######
        case "color":
            if (isset($_POST['submit']))
            {
                $acl = check(trim($_POST['acl']));
                $bcl = check(trim($_POST['bcl']));
                $ccl = check(trim($_POST['ccl']));
                $lcl = check(trim($_POST['lcl']));
                $tcol = check(trim($_POST['tcol']));
                $cntem = check(trim($_POST['cntem']));
                $ccolp = check(trim($_POST['ccolp']));
                $cdtim = check(trim($_POST['cdtim']));
                $cssip = check(trim($_POST['cssip']));
                $csnik = check(trim($_POST['csnik']));
                $conik = check(trim($_POST['conik']));
                $cadms = check(trim($_POST['cadms']));
                $cons = check(trim($_POST['cons']));
                $coffs = check(trim($_POST['coffs']));
                $cdinf = check(trim($_POST['cdinf']));
                $cpfon = check(trim($_POST['cpfon']));
                $ccfon = check(trim($_POST['ccfon']));
                $cctx = check(trim($_POST['cctx']));
                $pfon = intval(check(trim($_POST['pfon'])));
                mysql_query("update `users` set bgcolor='" . $acl . "',bclass='" . $bcl . "',cclass='" . $ccl . "',link='" . $lcl . "',tex='" . $tcol . "',pfon='" . $pfon . "',cpfon='" . $cpfon . "',ccfon='" . $ccfon . "',cctx='" . $cctx . "',cntem='" . $cntem .
                    "',ccolp='" . $ccolp . "',cdtim='" . $cdtim . "',cssip='" . $cssip . "',csnik='" . $csnik . "', conik='" . $conik . "',cadms='" . $cadms . "',cons='" . $cons . "',coffs='" . $coffs . "',cdinf='" . $cdinf . "'  where id='" . intval($_SESSION['pid']) .
                    "';");
                header("Location: usset.php?act=color&yes");
            } else
            {
                require ("../incfiles/head.php");
                require ("../incfiles/inc.php");
                if (isset($_GET['yes']))
                {
                    echo "Ваши цветовые настройки изменены!<br/>";
                }

                echo "<form action='usset.php?act=color' method='post' >Настройки цвета<br/>";
                echo "Отделить фоном ник,дату и т.д. от текста поста:<br/>Да&nbsp;&nbsp;&nbsp;&nbsp;";
                if ($datauser[pfon] == "1")
                {
                    echo "<input name='pfon' type='radio' value='1' checked='checked'/>";
                } else
                {
                    echo "<input name='pfon' type='radio' value='1' />";
                }
                echo " &nbsp; &nbsp; ";
                if ($datauser[pfon] == "0")
                {
                    echo "<input name='pfon' type='radio' value='0' checked='checked' />";
                } else
                {
                    echo "<input name='pfon' type='radio' value='0'/>";
                }
                echo "Нет<br/>";
                echo "Цвет фона<br/><select name='acl'><br/>";
                if (!empty($fon))
                {
                    echo "<option value= '" . $fon . "'>$arrcol[$fon]</option>";
                } else
                {
                    echo "<option value= '#666666'>Серый</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $fon || $key != "#666666")
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";
                echo "Первое поле<br/><select name='bcl'><br/>";
                if (!empty($clb))
                {
                    echo "<option value= '" . $clb . "'>$arrcol[$clb]</option>";
                } else
                {
                    echo "<option value= '#009999'>Бирюзовый</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $clb || $key != "#009999")
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";
                echo "Второе поле<br/><select name='ccl'><br/>";
                if (!empty($clc))
                {
                    echo "<option value= '" . $clc . "'>$arrcol[$clc]</option>";
                } else
                {
                    echo "<option value= '#0000FF'>Светло-синий</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $clc || $key != "#0000FF")
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";
                echo "Текст<br/><select name='tcol'><br/>";
                if (!empty($colt))
                {
                    echo "<option value= '" . $colt . "'>$arrcol[$colt]</option>";
                } else
                {
                    echo "<option value= '#000000'>Чёрный</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $colt || $key != "#000000")
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";
                echo "Ссылки<br/><select name='lcl'><br/>";
                if (!empty($clink))
                {
                    echo "<option value= '" . $clink . "'>$arrcol[$clink]</option>";
                } else
                {
                    echo "<option value= '#CCCCCC'>Серебряный</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $clink || $key != "#CCCCCC")
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";
                echo "Цвет названия темы,раздела<br/><select name='cntem'><br/>";
                if (!empty($cntem))
                {
                    echo "<option value= '" . $cntem . "'>$arrcol[$cntem]</option>";
                } else
                {
                    echo "<option value= '" . $clink . "'>$arrcol[$clink]</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $cntem || $key != $clink)
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";

                echo "Цвет фона ник,время и пр. в посте<br/><select name='cpfon'><br/>";
                if (!empty($cpfon))
                {
                    echo "<option value= '" . $cpfon . "'>$arrcol[$cpfon]</option>";
                } else
                {
                    echo "<option value= '" . $fon . "'>$arrcol[$fon]</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $cpfon || $key != "$fon")
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";

                echo "Цвет фона цитаты<br/><select name='ccfon'><br/>";
                if (!empty($ccfon))
                {
                    echo "<option value= '" . $ccfon . "'>$arrcol[$ccfon]</option>";
                } else
                {
                    echo "<option value= '" . $fon . "'>$arrcol[$fon]</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $ccfon || $key != "$fon")
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";

                echo "Цвет текста цитаты<br/><select name='cctx'><br/>";
                if (!empty($cctx))
                {
                    echo "<option value= '" . $cctx . "'>$arrcol[$cctx]</option>";
                } else
                {
                    echo "<option value= '" . $colt . "'>$arrcol[$colt]</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $cctx || $key != "$colt")
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";

                echo "Кол-во постов<br/><select name='ccolp'><br/>";
                if (!empty($ccolp))
                {
                    echo "<option value= '" . $ccolp . "'>$arrcol[$ccolp]</option>";
                } else
                {
                    echo "<option value= '" . $colt . "'>$arrcol[$colt]</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $ccolp || $key != "$colt")
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";

                echo "Время и дата<br/><select name='cdtim'><br/>";
                if (!empty($cdtim))
                {
                    echo "<option value= '" . $cdtim . "'>$arrcol[$cdtim]</option>";
                } else
                {
                    echo "<option value= '" . $colt . "'>$arrcol[$colt]</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $cdtim || $key != $colt)
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";

                echo "Созд. и посл. написавший в теме<br/><select name='cssip'><br/>";
                if (!empty($cssip))
                {
                    echo "<option value= '" . $cssip . "'>$arrcol[$cssip]</option>";
                } else
                {
                    echo "<option value= '" . $colt . "'>$arrcol[$colt]</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $cssip || $key != $colt)
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";

                echo "Свой ник<br/><select name='csnik'><br/>";
                if (!empty($csnik))
                {
                    echo "<option value= '" . $csnik . "'>$arrcol[$csnik]</option>";
                } else
                {
                    echo "<option value= '" . $colt . "'>$arrcol[$colt]</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $csnik || $key != $colt)
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";

                echo "Чужой ник<br/><select name='conik'><br/>";
                if (!empty($conik))
                {
                    echo "<option value= '" . $conik . "'>$arrcol[$conik]</option>";
                } else
                {
                    echo "<option value= '" . $clink . "'>$arrcol[$clink]</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $conik || $key != $clink)
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";

                echo "Адмстатус<br/><select name='cadms'><br/>";
                if (!empty($cadms))
                {
                    echo "<option value= '" . $cadms . "'>$arrcol[$cadms]</option>";
                } else
                {
                    echo "<option value= '" . $colt . "'>$arrcol[$colt]</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $cadms || $key != $colt)
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";

                echo "Статус [ON]<br/><select name='cons'><br/>";
                if (!empty($cons))
                {
                    echo "<option value= '" . $cons . "'>$arrcol[$cons]</option>";
                } else
                {
                    echo "<option value= '" . $clink . "'>$arrcol[$clink]</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $cons || $key != $clink)
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";

                echo "Статус [Off]<br/><select name='coffs'><br/>";
                if (!empty($coffs))
                {
                    echo "<option value= '" . $coffs . "'>$arrcol[$coffs]</option>";
                } else
                {
                    echo "<option value= '" . $colt . "'>$arrcol[$colt]</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $coffs || $key != $colt)
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";

                echo "Доп. информация<br/><select name='cdinf'><br/>";
                if (!empty($cdinf))
                {
                    echo "<option value= '" . $cdinf . "'>$arrcol[$cdinf]</option>";
                } else
                {
                    echo "<option value= '" . $clink . "'>$arrcol[$clink]</option>";
                }
                foreach ($arrcol as $key => $val)
                {
                    if ($key != $cdinf || $key != $clink)
                    {
                        echo "<option value= '" . $key . "'>$val</option>";
                    }
                }
                echo "</select><br/>";
                echo "<input type='submit' name='submit' value='ok'/></form><br/>";
                $th = mysql_query("select * from `themes`;");
                $th2 = mysql_num_rows($th);
                echo "<a href='usset.php?act=other'>Готовые цветовые схемы ($th2)</a><br/>";
                echo "<a href='usset.php?act=save'>Сохранить свою цветовую схему</a><br/>";

                echo "<a href='usset.php?act=reset'>Вернуть цвета по умолчанию</a><br/>";
                echo "<a href='usset.php?act=help'>Справка по цветам</a><br/>";
                echo "<a href='usset.php'>Общие настройки</a><br/>";
            }


            break;

            ##########

        case "yes":


            $pereh = intval(check(trim($_POST['pereh'])));
            $offgr = intval(check(trim($_POST['offgr'])));
            $offsm = intval(check(trim($_POST['offsm'])));
            $offtr = intval(check(trim($_POST['offtr'])));
            $offpg = intval(check(trim($_POST['offpg'])));
            $sdvig = intval(check(trim($_POST['sdvig'])));

            mysql_query("update `users` set  sdvig='" . $sdvig . "',offgr='" . $offgr . "',pereh='" . $pereh . "',offsm='" . $offsm . "',offtr='" . $offtr . "',offpg='" . $offpg . "' where id='" . intval($_SESSION['pid']) . "';");
            header("Location: usset.php?yes");
            break;
            #############
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

                mysql_query("update `users` set chmes='" . $chmess . "',carea='" . $charea . "',timererfesh='" . $refresh . "',nastroy='" . $nastr . "' where id='" . intval($_SESSION['pid']) . "';");
                header("Location: usset.php?act=chat&yes");


            } else
            {
                require ("../incfiles/head.php");
                require ("../incfiles/inc.php");
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
                if (!empty($nastroy))
                {
                    echo "<option>$nastroy</option>";
                }
                foreach ($nastr as $v)
                {
                    if ($v != $nastroy)
                    {
                        echo "<option>$v</option>";
                    }
                }
                echo "</select><br/>";
                if ($dostsadm == 1)
                {
                    if (in_array($nastroy, $nastr))
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

                mysql_query("update `users` set nmenu='" . $nmenu . "',farea='" . $farea . "',upfp='" . $upfp . "',kolanywhwere='" . $kolmess . "' where id='" . intval($_SESSION['pid']) . "';");
                header("Location: usset.php?act=forum&yes");


            } else
            {
                require ("../incfiles/head.php");
                require ("../incfiles/inc.php");

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
                require ("../incfiles/head.php");
                require ("../incfiles/inc.php");
                echo "Ошибка!<br/>";
                require ("../incfiles/inc.php");
                exit;
            }
            $id = intval(check($_GET['id']));
            $th = mysql_query("select * from `themes` where id='" . $id . "';");
            $thm = mysql_fetch_array($th);
            if (isset($_POST['submit']))
            {
                mysql_query("update `users` set pfon='" . $thm[pfon] . "',cpfon='" . $thm[cpfon] . "',ccfon='" . $thm[ccfon] . "',cctx='" . $thm[cctx] . "',bgcolor='" . $thm[bgcolor] . "',tex='" . $thm[tex] . "',link='" . $thm[link] . "',bclass='" . $thm[bclass] .
                    "',cntem='" . $thm[cntem] . "',ccolp='" . $thm[ccolp] . "',cdtim='" . $thm[cdtim] . "',cssip='" . $thm[cssip] . "',csnik='" . $thm[csnik] . "',cclass='" . $thm[cclass] . "',conik='" . $thm[conik] . "',cadms='" . $thm[cadms] . "',cons='" . $thm[cons] .
                    "',coffs='" . $thm[coffs] . "',cdinf='" . $thm[cdinf] . "' where id='" . intval(check($_SESSION['pid'])) . "';");
                header("Location: usset.php?act=color&yes");
            } else
            {
                require ("../incfiles/head.php");
                require ("../incfiles/inc.php");

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
            require ("../incfiles/head.php");
            require ("../incfiles/inc.php");
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

            ###############
        case "save":
            require ("../incfiles/head.php");
            require ("../incfiles/inc.php");
            if (isset($_GET['yes']))
            {
                mysql_query("update `themes` set pfon='" . $pfon . "',cpfon='" . $cpfon . "',ccfon='" . $ccfon . "',cctx='" . $cctx . "',bgcolor='" . $fon . "',tex='" . $colt . "',link='" . $clink . "',bclass='" . $clb . "',cntem='" . $cntem . "',ccolp='" .
                    $ccolp . "',cdtim='" . $cdtim . "',cssip='" . $cssip . "',csnik='" . $csnik . "',cclass='" . $clc . "',time='" . $realtime . "',conik='" . $conik . "',cadms='" . $cadms . "',cons='" . $cons . "',coffs='" . $coffs . "',cdinf='" . $cdinf .
                    "' where name='" . $login . "';");
                echo "Ваша цветовая схема перезаписана!<br/><a href='usset.php'>В настройки</a><br/>";
            } else
            {
                $q1 = mysql_query("select * from `themes` where name='" . $login . "';");
                $q2 = mysql_num_rows($q1);
                if ($q2 == 1)
                {
                    echo " У Вас уже есть сохраненная цветовая схема.<br/>Перезаписать?<br/><a href='usset.php?act=save&amp;yes'>Да</a> | <a href='usset.php?act=forum'>Нет</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                mysql_query("insert into `themes` values(0,'" . $login . "','" . $realtime . "','" . $fon . "','" . $colt . "','" . $clink . "','" . $clb . "','" . $clc . "','" . $pfon . "','" . $cpfon . "','" . $ccfon . "','" . $cctx . "','" . $cntem .
                    "','" . $ccolp . "','" . $cdtim . "','" . $cssip . "','" . $csnik . "','" . $conik . "','" . $cadms . "','" . $cons . "','" . $coffs . "','" . $cdinf . "');");
                echo "Ваша цветовая схема сохранена!<br/><a href='usset.php'>В настройки</a><br/>";
            }
            break;
            ######################
        case "reset":

            if (isset($_GET['yes']))
            {
                mysql_query("update `users` set pfon='0',cpfon='',ccfon='',cctx='',bgcolor='',tex='',link='',bclass='',cntem='',ccolp='',cdtim='',cssip='',csnik='',cclass='',conik='',cadms='',cons='',coffs='',cdinf='' where id='" . intval(check($_SESSION['pid'])) .
                    "';");
                header("location: usset.php?act=color&yes");
            } else
            {
                require ("../incfiles/head.php");
                require ("../incfiles/inc.php");
                echo "Применить цвета по умолчанию?<br/><a href='usset.php?act=reset&amp;yes'>Да</a> | <a href='usset.php?act=forum'>Нет</a><br/>";
            }
            break;
            #########
        default:
            require ("../incfiles/head.php");
            require ("../incfiles/inc.php");

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
            if ($pereh == "0")
            {
                echo "<input name='pereh' type='radio' value='0' checked='checked'/>";
            } else
            {
                echo "<input name='pereh' type='radio' value='0' />";
            }
            echo " &nbsp; &nbsp; ";
            if ($pereh == "1")
            {
                echo "<input name='pereh' type='radio' value='1' checked='checked' />";
            } else
            {
                echo "<input name='pereh' type='radio' value='1'/>";
            }
            echo "Выкл<br/>";

            echo "<input type='submit' value='ok'/></form><br/>";
            echo "<a href='usset.php?act=color'>Настройки цвета</a><br/>";
            echo "<a href='usset.php?act=forum'>Настройки форума</a><br/>";
            echo "<a href='usset.php?act=chat'>Настройки чата</a><br/><br/>";
            break;
    }
} else
{
    require ("../incfiles/head.php");
    require ("../incfiles/inc.php");
    print "Ошибка!<br/>";
}
require ("../incfiles/end.php");
?>
