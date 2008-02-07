<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.0.0 RC1                                                        //
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
session_name("SESID");
session_start();
$headmod = 'anketa';
$textl = 'Анкета';
require ("../incfiles/db.php");
require ("../incfiles/func.php");
require ("../incfiles/data.php");
require ("../incfiles/head.php");
require ("../incfiles/inc.php");
require ("../incfiles/char.php");
$tti = $realtime;

if (!empty($_SESSION['pid']))
{


    if (empty($_GET['user']))
    {
        $user = $idus;
    } else
    {
        $user = $_GET['user'];
    }

    $user = intval(check(trim($user)));
    $q = @mysql_query("select * from `users` where id='" . $user . "';");
    $arr = @mysql_fetch_array($q);
    $arr2 = mysql_num_rows($q);
    if ($arr2 == 0)
    {
        echo "Пользователя с таким id не существует!<br/>";
        require ("../incfiles/end.php");
        exit;
    }
    if (!empty($_GET['act']))
    {
        $act = check($_GET['act']);
    }

    if ($act == "statistic")
    {

        echo "Статистика<br/>";
        echo "Комментариев: $arr[komm] <br/>";
        echo "Сообщений в форуме: $arr[postforum] <br/>";
        echo "Сообщений в чате: $arr[postchat] <br/>";
        echo "Ответов в чате: $arr[otvetov] <br/>";
        echo "Игровой баланс: $arr[balans] <br/>";
        echo "<a href='anketa.php?user=" . $arr[id] . "&amp;'>В анкету</a><br/>";
        require ("../incfiles/end.php");
        exit;
    }

    if ($user == $idus)
    {
        switch ($act)
        {
            case "name":
                echo "<form action='anketa.php?user=" . $idus . "&amp;act=editname' method='post'>Изменить имя(max. 15):<br/><input type='text' name='nname' value='" . $arr[imname] .
                    "'/><br/><input type='submit'  value='ok'/></form><br/><a href='anketa.php?user=" . $idus . "'>Назад</a><br/>";
                break;

            case "editname":
                $nname = check(trim($_POST['nname']));
                $nname = utfwin($nname);
                $nname = substr($nname, 0, 15);
                $nname = winutf($nname);
                mysql_query("update `users` set imname='" . $nname . "' where id='" . $_SESSION['pid'] . "';");
                echo "Принято: $nname<br/><a href='anketa.php?user=" . $idus . "'>Продолжить</a><br/>";
                break;

            case "par":
                echo "<form action='anketa.php?user=" . $idus .
                    "&amp;act=editpar' method='post'>Старый пароль:<br/><input type='text' name='par1'/><br/>Новый пароль:<br/><input type='text' name='par2'/><br/>Подтвердите пароль:<br/><input type='text' name='par3'/><br/><input type='submit' value='ok'/></form><br/><a href='anketa.php?user=" .
                    $idus . "'>Назад</a><br/>";
                break;

            case "editpar":
                $par1 = check(trim($_POST['par1']));
                $par11 = md5(md5($par1));
                $passw = $arr[password];
                $par2 = check(trim($_POST['par2']));
                $par3 = check(trim($_POST['par3']));
                $par22 = md5(md5($par2));
                if ($par11 !== $passw)
                {
                    echo "Неверно указан текущий пароль<br/><a href='anketa.php?act=par&amp;user=" . $idus . "'>Повторить</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                if ($par2 !== $par3)
                {
                    echo "Вы ошиблись при подтверждении нового пароля<br/><a href='anketa.php?act=par&amp;user=" . $idus . "'>Повторить</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                if ($par2 == "")
                {
                    echo "Вы не ввели новый пароль<br/><a href='anketa.php?act=par&amp;user=" . $idus . "'>Повторить</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                mysql_query("update `users` set password='" . $par22 . "' where id='" . $_SESSION['pid'] . "';");
                echo "Пароль изменен,войдите на сайт заново<br/><a href='../in.php'>Вход</a><br/>";
                unset($_SESSION['pid']);
                unset($_SESSION['provc']);
                setcookie('cpide', '');
                setcookie('ckode', '');
                setcookie(session_name(), '');
                session_destroy();
                break;

            case "gor":
                echo "<form action='anketa.php?user=" . $idus . "&amp;act=editgor' method='post'>Изменить город(max. 20):<br/><input type='text' name='ngor' value='" . $arr[live] .
                    "'/><br/><input type='submit' value='ok'/></form><br/><a href='anketa.php?user=" . $idus . "'>Назад</a><br/>";
                break;

            case "editgor":
                $ngor = check(trim($_POST['ngor']));
                $ngor = utfwin($ngor);
                $ngor = substr($ngor, 0, 20);
                $ngor = winutf($ngor);
                mysql_query("update `users` set live='" . $ngor . "' where id='" . $_SESSION['pid'] . "';");
                echo "Принято: $ngor<br/><a href='anketa.php?user=" . $idus . "'>Продолжить</a><br/>";
                break;

            case "inf":
                echo "<form action='anketa.php?user=" . $idus . "&amp;act=editinf' method='post'>Изменить инфу(max. 500):<br/><input type='text' name='ninf' value='" . $arr[about] .
                    "'/><br/><input type='submit' value='ok'/></form><br/><a href='anketa.php?user=" . $idus . "'>Назад</a><br/>";
                break;

            case "editinf":
                $ninf = check(trim($_POST['ninf']));
                $ninf = utfwin($ninf);
                $ninf = substr($ninf, 0, 500);
                $ninf = winutf($ninf);
                mysql_query("update `users` set about='" . $ninf . "' where id='" . $_SESSION['pid'] . "';");
                echo "Принято: $ninf<br/><a href='anketa.php?user=" . $idus . "'>Продолжить</a><br/>";
                break;

            case "icq":
                echo "<form action='anketa.php?user=" . $idus . "&amp;act=editicq' method='post'>Изменить ICQ:<br/><input type='text' name='nicq' value='" . $arr[icq] . "'/><br/><input type='submit' value='ok'/></form><br/><a href='anketa.php?user=" . $idus .
                    "'>Назад</a><br/>";
                break;

            case "editicq":
                $nicq = intval(check(trim($_POST['nicq'])));

                $nicq = substr($nicq, 0, 9);
                mysql_query("update `users` set icq='" . $nicq . "' where id='" . $_SESSION['pid'] . "';");
                echo "Принято: $nicq<br/><a href='anketa.php?user=" . $idus . "'>Продолжить</a><br/>";
                break;

            case "mobila":
                echo "<form action='anketa.php?user=" . $idus . "&amp;act=editmobila' method='post'>Изменить модель телефона(max.50):<br/><input type='text' name='nmobila' value='" . $arr[mibile] .
                    "' /><br/><input type='submit' value='ok'/></form><br/><a href='anketa.php?user=" . $idus . "'>Назад</a><br/>";
                break;

            case "editmobila":
                $nmobila = check(trim($_POST['nmobila']));
                $nmobila = utfwin($nmobila);
                $nmobila = substr($nmobila, 0, 50);
                $nmobila = winutf($nmobila);
                mysql_query("update `users` set mibile='" . $nmobila . "' where id='" . $_SESSION['pid'] . "';");
                echo "Принято: $nmobila<br/><a href='anketa.php?user=" . $idus . "'>Продолжить</a><br/>";
                break;

            case "dr":
                echo "<form action='anketa.php?user=" . $idus . "&amp;act=editdr' method='post'>Изменить дату рождения:<br/><select name='user_day' class='textbox'><option>$arr[dayb]</option>";
                $i = 1;
                while ($i <= 31)
                {
                    echo "<option value='" . $i . "'>$i</option>";
                    ++$i;
                }
                $mnt = $arr[monthb];
                echo "</select>
<select name='user_month' class='textbox'><option value='" . $mnt . "'>$mesyac[$mnt]</option>";
                $i = 1;
                while ($i <= 12)
                {
                    echo "<option value='" . $i . "'>$mesyac[$i]</option>";
                    ++$i;
                }
                echo "</select>
<select name='user_year' class='textbox'><option>$arr[yearofbirth]</option>";
                $i = 1950;
                while ($i <= 2000)
                {
                    echo "<option value='" . $i . "'>$i</option>";
                    ++$i;
                }
                echo "</select><br/><input type='submit' value='ok'/></form><br/><a href='anketa.php?user=" . $idus . "'>Назад</a><br/>";
                break;

            case "editdr":
                $user_day = intval(check(trim($_POST['user_day'])));
                $user_month = intval(check(trim($_POST['user_month'])));
                $user_year = intval(check(trim($_POST['user_year'])));
                mysql_query("update `users` set dayb='" . $user_day . "', monthb='" . $user_month . "' ,yearofbirth='" . $user_year . "' where id='" . $_SESSION['pid'] . "';");
                echo "Принято: $user_day $mesyac[$user_month] $user_year<br/><a href='anketa.php?user=" . $idus . "'>Продолжить</a><br/>";
                break;

            case "site":
                echo "<form action='anketa.php?user=" . $idus . "&amp;act=editsite' method='post'>Изменить сайт(max. 50):<br/><input type='text' name='nsite' value='" . $arr[www] .
                    "'/><br/><input type='submit' value='ok'/></form><br/><a href='anketa.php?user=" . $idus . "'>Назад</a><br/>";
                break;

            case "editsite":
                $nsite = check(trim($_POST['nsite']));
                $nsite = utfwin($nsite);
                $nsite = substr($nsite, 0, 50);
                $nsite = winutf($nsite);
                mysql_query("update `users` set www='" . $nsite . "' where id='" . $_SESSION['pid'] . "';");
                echo "Принято: $nsite<br/><a href='anketa.php?user=" . $idus . "'>Продолжить</a><br/>";
                break;

            case "mail":
                if ($arr[mailact] == 0)
                {
                    echo 'Ваш адрес e-mail необходимо<a href="anketa.php?act=activmail&amp;user=' . $idus . '"> активировать</a><br/>
(<a href="anketa.php?act=helpactiv&amp;user=' . $idus . '">Зачем это нужно?</a>)<br/>';
                }
                echo "<form action='anketa.php?user=" . $idus . "&amp;act=editmail' method='post'>Изменить E-mail(max. 50):<br/><input type='text' name='nmail' value='" . $arr[mail] . "'/><br/>";
                if ($arr[mailact] == 1)
                {
                    switch ($arr[mailvis])
                    {
                        case 1:
                            echo "<input type='checkbox' name='nmailvis' value='0'/>Скрыть<br/>";
                            break;
                        case 0:
                            echo "<input type='checkbox' name='nmailvis' value='1'/>Показать<br/>";
                            break;
                    }
                }
                echo "<input type='submit' value='ok'/></form><br/><a href='anketa.php?user=" . $idus . "'>Назад</a><br/>";
                if ($arr[mailact] == 0)
                {
                    echo "<a href='anketa.php?act=activmail&amp;user=" . $idus . "&amp;continue'>Продолжить активацию</a><br/>";
                }
                break;

            case "helpactiv":
                include ("../pages/actmail.$ras_pages");
                echo "<a href='anketa.php?act=mail'>Назад</a><br/>";
                break;

            case "editmail":
                $nmail = htmlspecialchars($_POST[nmail]);

                if (!eregi("^[a-z0-9\._-]+@[a-z0-9\._-]+\.[a-z]{2,4}\$", $nmail))
                {
                    echo "Некорректный формат e-mail адреса!";
                    echo "<a href='anketa.php?action=mail'>Повторить</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                $nmail = utfwin($nmail);
                $nmail = substr($nmail, 0, 50);
                $nmail = winutf($nmail);
                $nmailvis = intval(check($_POST['nmailvis']));
                if ($nmail != $arr[mail])
                {
                    $nmailact = 0;
                } else
                {
                    $nmailact = $arr[mailact];
                }

                mysql_query("update `users` set mail='" . $nmail . "', mailvis='" . $nmailvis . "', mailact='" . $nmailact . "' where id='" . $_SESSION['pid'] . "';");

                echo "Принято: $nmail<br/><a href='anketa.php?user=" . $idus . "'>Продолжить</a><br/>";
                break;

            case "activmail":
                if (isset($_GET['continue']))
                {
                    if (isset($_POST['submit']))
                    {
                        if (intval($_POST['provact']) == $arr[kod])
                        {

                            mysql_query("update `users` set mailact='1' where id='" . $_SESSION['pid'] . "';");
                            unset($_SESSION['activ']);
                            echo "E-mail адрес успешно активирован<br/>";
                            echo "<a href='anketa.php?user=" . $idus . "'>В анкету</a><br/>";
                        } else
                        {
                            echo "Неверный код<br/>";
                            echo "<a href='anketa.php?act=activmail&amp;user=" . $idus . "&amp;continue'>Повторить</a><br/>";
                        }
                    } else
                    {
                        echo "<form action='anketa.php?user=" . $idus . "&amp;act=activmail&amp;continue' method='post'>Код активации:<br/><input type='text' name='provact'/><br/><input type='submit' name='submit' value='ok'/></form><br/><a href='anketa.php?user=" .
                            $idus . "'>Назад</a><br/>";
                    }
                    require ("../incfiles/end.php");
                    exit;
                }
                if ($_SESSION['activ'] != 1)
                {
                    $mailcode = rand(100000, 999999);
                    $subject = "E-mail activation";
                    $mail = "Здравствуйте " . $login . "\r\nКод для активации e-mail адреса " . $mailcode . "\r\nТеперь Вы можете продолжить активацию\r\n";

                    $subject = utfwin($subject);

                    $name = utfwin($name);
                    $mail = utfwin($mail);

                    $name = convert_cyr_string($name, 'w', 'k');
                    $subject = convert_cyr_string($subject, 'w', 'k');
                    $mail = convert_cyr_string($mail, 'w', 'k');
                    $adds = "From: <" . $emailadmina . ">\n";

                    $adds .= "X-sender: <" . $emailadmina . ">\n";
                    $adds .= "Content-Type: text/plain; charset=koi8-r\n";

                    $adds .= "MIME-Version: 1.0\r\n";
                    $adds .= "Content-Transfer-Encoding: 8bit\r\n";
                    $adds .= "X-Mailer: PHP v." . phpversion();

                    mail($arr[mail], $subject, $mail, $adds);
                    mysql_query("update `users` set kod='" . $mailcode . "' where id='" . $_SESSION['pid'] . "';");
                    echo 'Код для активации выслан по указанному адресу<br/>';
                    $_SESSION['activ'] = 1;
                } else
                {
                    echo "Код для активации уже выслан<br/>";
                }
                echo "<a href='anketa.php?user=" . $idus . "'>В анкету</a><br/>";
                break;

            default:
                echo " Моя анкета<br/>";
                $mmon = $arr[monthb];
                echo "<a href='anketa.php?act=par&amp;user=" . $idus . "'>Пароль:</a><br/>";

                echo "<a href='anketa.php?act=name'>Имя:</a>$arr[imname]<br/>";
                echo "<a href='anketa.php?act=gor'>Город:</a>$arr[live]<br/>";
                echo "<a href='anketa.php?act=inf'>О себе:</a>$arr[about]<br/>";
                echo "<a href='anketa.php?act=icq'>ICQ:</a>$arr[icq]<br/>";
                echo "<a href='anketa.php?act=mail'>E-mail:</a>$arr[mail] ";
                if ($arr[mailact] == 0)
                {
                    echo "<font color='" . $clink . "'>(!)</font>";
                }
                echo "<br/><a href='anketa.php?act=mobila'>Мобила:</a>$arr[mibile]<br/>";
                echo "<a href='anketa.php?act=dr'>Дата рождения:</a>$arr[dayb] $mesyac[$mmon] $arr[yearofbirth]<br/>";
                echo "<a href='anketa.php?act=site'>Сайт:</a>$arr[www]<br/>";


                echo "<a href='anketa.php?act=statistic'>Моя статистика</a><br/>";
                if ($dostadm == 1)
                {
                    echo "<a href='../" . $admp . "/editusers.php?act=edit&amp;user=" . $idus . "'>Редактировать свои данные</a><br/>";
                }
                require ("../incfiles/end.php");
                exit;
                break;
        }
    }

    if ($act == "ban")
    {

        $jjtti = round(($arr[bantime] - $realtime) / 60);
        if ($jjtti > 60)
        {
            $jjtti = round($jjtti / 60) . ' час.';
        } else
        {
            $jjtti = $jjtti . ' мин';
        }
        $whoban = $arr[who];
        $whyban = $arr[why];
        if ($arr[ban] == "1" && $arr[bantime] > $realtime || $arr[ban] == "2")
        {
            echo "Юзер забанен<br/>";
            echo "Забанил <font color='" . $clink . "'>$whoban</font><br/>";
            if ($arr[why] == "")
            {
                echo "Причина не указана<br/>";
            } else
            {
                echo "Причина:<font color='" . $clink . "'> $whyban</font><br/>";
            }
            if ($arr[ban] == "1")
            {
                echo "Время до окончания бана: $jjtti<br/>";
            }
            if ($arr[ban] == "2")
            {
                echo "Бан активен до отмены<br/>";
            }
        }
        echo "<a href='anketa.php?user=" . $arr[id] . "'>Назад</a><br/>";
    }
    if ($act == "kolvo")
    {
        $kolvob = mysql_query("select * from `bann` WHERE `user` = '" . $arr['name'] . "' and `kolv` = 'yes' order by time desc;");

        while ($nb = mysql_fetch_array($kolvob))
        {
            $d = $i / 2;
            $d1 = ceil($d);
            $d2 = $d1 - $d;
            $d3 = ceil($d2);
            if ($d3 == 0)
            {
                $div = "<div class='b'>";
            } else
            {
                $div = "<div class='c'>";
            }
            $vrb = $nb[time] + $sdvig * 3600;
            $vrb1 = date("d.m.y / H:i", $vrb);
            echo "$div $vrb1 <br/>Вид наказания:";
            switch ($nb[type])
            {
                case "3":
                    echo "Бан<br/>Забанил";
                    break;
                case "2":
                    echo "Пинок из чата<br/>Пнул";
                    break;
                case "1":
                    echo "Пинок из форума<br/>Пнул";
                    break;
            }
            echo ": <font color='" . $clink . "'> $nb[admin]</font><br/>";
            if ($nb[why] == "")
            {
                echo "Причина не указана<br/>";
            } else
            {
                echo "Причина:<font color='" . $clink . "'> $nb[why]</font><br/>";
            }

            $kolvob1 = mysql_query("select * from `users` WHERE `name` = '" . $arr['name'] . "' and `time` = '" . $nb['time'] . "' ;");
            $nb1 = mysql_fetch_array($kolvob1);
            $tti = time();

            if ($nb1[ban] == "1" && $nb1[bantime] > $realtime)
            {
                echo "<font color='" . $clink . "'>БАН АКТИВЕН!</font><br/>";
            }
            if ($nb1[ban] == "2")
            {
                echo "<font color='" . $clink . "'>БАН АКТИВЕН ДО ОТМЕНЫ!</font><br/>";
            }
            if (($nb1[fban] == "1" && $nb1[ftime] > $realtime) || ($nb1[chban] == "1" && $nb1[chtime] > $realtime))
            {
                echo "<font color='" . $clink . "'>ПИНОК АКТИВЕН!</font><br/>";
            }
            echo "</div>";
            ++$i;
        }
        echo "<hr/><a href='anketa.php?user=" . $arr[id] . "'>Назад</a><br/>";
    }

    if ($act == "")
    {
        echo "$arr[name] (id: $arr[id])";
        $ontime = $arr[lastdate];
        $ontime2 = $ontime + 300;
        $preg = $arr[preg];
        $regadm = $arr[regadm];

        if ($realtime > $ontime2)
        {
            echo " [Off]<br/>";
        } else
        {
            echo " [ON]<br/>";
        }

        if ($arr[dayb] == $day && $arr[monthb] == $mon)
        {
            echo "<font color='" . $clink . "'>ИМЕНИННИК!!!</font><br/>";
        }


        switch ($arr[rights])
        {
            case 1:
                echo 'Киллер';
                break;
            case 2:
                echo 'Модер чата';
                break;
            case 3:
                echo 'Модер форума';
                break;
            case 4:
                echo 'Зам. админа по загрузкам';
                break;
            case 5:
                echo 'Зам. админа по библиотеке';
                break;
            case 6:
                echo 'Супермодератор';
                break;
            case 7:
                echo 'Админ';
                break;
            default:
                echo 'Юзер';
        }
        echo '<br/>';
        if (!empty($arr[status]))
        {
            $stats = $arr[status];
            $stats = smiles($stats);
            $stats = smilescat($stats);

            $stats = smilesadm($stats);
            echo "$stats<br/>";
        }
        if ($dostadm == "1")
        {
            if ($preg == 0 && $regadm == "")
            {
                echo "Ожидает подтверждения регистрации<br/>";
            }
            if ($preg == 0 && $regadm != "")
            {
                echo "Регистрацию отклонил $regadm<br/>";
            }
            if ($preg == 1 && $regadm != "")
            {
                echo "Регистрацию подтвердил $regadm<br/>";
            }
            if ($preg == 1 && $regadm == "")
            {
                echo "Регистрация без подтверждения<br/>";
            }
        }
        if ($arr[ban] == "1" && $arr[bantime] > $realtime || $arr[ban] == "2")
        {
            echo "<a href='anketa.php?act=ban&amp;user=" . $arr[id] . "'><font color='red'>Бан!</font></a><br/>";
        }
        ##########
        $kol = mysql_query("select * from `bann` WHERE `user` = '" . $arr['name'] . "' and `kolv` = 'yes';");
        $kol2 = mysql_num_rows($kol);
        if ($kol2 != 0)
        {
            echo "<a href='anketa.php?act=kolvo&amp;user=" . $arr[id] . "'>Всего наказаний: $kol2</a><br/>";
        }

        ##############
        echo "Имя: $arr[imname]<br/>";
        if ($arr[sex] == "m")
        {
            echo "Парень<br/>";
        }
        if ($arr[sex] == "zh")
        {
            echo "Девушка<br/>";
        }
        if (!empty($arr[dateofbirth]))
        {
            echo "Дата рождения: $arr[dayb] $mesyac[$mmon] $arr[yearofbirth]<br/>";
        }
        if ($arr['sex'] == "m")
        {
            echo "Зарегистрирован";
        }
        if ($arr['sex'] == "zh")
        {
            echo "Зарегистрирована";
        }
        echo ": " . date("d.m.Y", $arr[datereg]) . "<br/>";
        if ($arr['sex'] == "m")
        {
            echo "Последний раз был";
        }
        if ($arr['sex'] == "zh")
        {

            echo "Последний раз была";
        }
        echo ": " . date("d.m.Y (H:i)", $arr[lastdate]) . "<br/>";

        if ($arr[mailact] == 1)
        {

            if (!empty($arr[mail]))
            {
                echo "E-mail:";
                if ($arr[mailvis] == 1)
                {
                    echo "$arr[mail]<br/>";
                } else
                {
                    echo "скрыт<br/>";
                }
            }
        }
        if (!empty($arr[icq]))
        {
            echo '<img src="http://web.icq.com/whitepages/online?icq=' . $arr[icq] . '&amp;img=5" alt=""/> ICQ:' . $arr[icq] . ' <br/> ';
        }

        if (!empty($arr[www]) && $arr[www] !== "http://" && stristr($arr[www], "http://"))
        {
            $sait = str_replace("http://", "", $arr[www]);
            echo "Сайт: <a href='" . $arr[www] . "'>$sait</a><br/>";
        }
        if (!empty($arr[about]))
        {
            echo "О себе: $arr[about]<br/>";
        }
        if (!empty($arr[live]))
        {
            echo "Город: $arr[live]<br/>";
        }
        if (!empty($arr[mibile]))
        {
            echo "Мобила: $arr[mibile]<br/>";
        }

        $svr = $arr[vremja];
        if ($svr >= "3600")
        {
            $hvr = ceil($svr / 3600) - 1;
            $svr1 = $svr - $hvr * 3600;
            $mvr = ceil($svr1 / 60) - 1;
            $ivr = $svr1 - $mvr * 60;
            if ($ivr == "60")
            {
                $ivr = "59";
            }
            $sitevr = "$hvr ч. $mvr мин. $ivr сек. ";
        } else
        {
            if ($svr >= "60")
            {
                $mvr = ceil($svr / 60) - 1;
                $ivr = $svr - $mvr * 60;
                if ($ivr == "60")
                {
                    $ivr = "59";
                }
                $sitevr = "$mvr мин. $ivr сек. ";
            } else
            {
                $ivr = $svr;
                $sitevr = "$ivr сек. ";
            }
        }
        if ($arr['sex'] == "m")
        {
            echo "Провел ";
        }
        if ($arr['sex'] == "zh")
        {
            echo "Провела ";
        }

        echo "времени на сайте: $sitevr<br/>";
        echo "<a href='anketa.php?act=statistic&amp;user=" . $arr[id] . "'>Статистика юзера</a><br/>";
        if (!empty($_SESSION['pid']))
        {

            $contacts = mysql_query("select * from `privat` where me='" . $login . "' and cont='" . $arr['name'] . "';");
            $conts = mysql_num_rows($contacts);
            if ($conts != 1)
            {
                echo "<a href='cont.php?act=edit&amp;id=" . $user . "&amp;add=1'>Добавить в контакты</a><br/>";
            } else
            {
                echo "<a href='cont.php?act=edit&amp;id=" . $user . "'>Удалить из контактов</a><br/>";
            }
            $igns = mysql_query("select * from `privat` where me='" . $login . "' and ignor='" . $arr['name'] . "';");
            $ignss = mysql_num_rows($igns);
            if ($igns != 1)
            {
                if ($arr[rights] == 0 && $arr[name] != $nickadmina && $arr[name] != $nickadmina)
                {
                    echo "<a href='ignor.php?act=edit&amp;id=" . $user . "&amp;add=1'>Добавить в игнор</a><br/>";
                }
            } else
            {
                echo "<a href='ignor.php?act=edit&amp;id=" . $user . "'>Удалить из игнора</a><br/>";
            }
            echo "<a href='pradd.php?act=write&amp;adr=" . $arr[id] . "'>Написать в приват</a><br/>";
        }

        if ($dostkmod == "1")
        {
            echo "$arr[ip]<br/>$arr[browser]<br/>";
            echo "<a href='../" . $admp . "/zaban.php?user=" . $arr['id'] . "'>Банить</a><br/>";
        }
        if ($dostadm == "1")
        {
            echo "<a href='../" . $admp . "/editusers.php?act=edit&amp;user=" . $arr[id] . "'>Редактировать</a><br/><a href='../" . $admp . "/editusers.php?act=del&amp;user=" . $arr[id] . "'>Удалить</a><br/>";
        }
    }
} else
{
    echo "Вы не авторизованы!<br/>";
}
require ("../incfiles/end.php");
?>



