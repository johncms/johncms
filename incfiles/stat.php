<?
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

defined('_IN_JOHNCMS') or die('Error:restricted access');

// Дата последней новости
function dnews()
{
    if (!empty($_SESSION['uid']))
    {
        global $sdvig;
    } else
    {
        global $sdvigclock;
        $sdvig = $sdvigclock;
    }
    $req = mysql_query("select `time` from `news` order by `time` desc;");
    $res = mysql_fetch_array($req);
    $vrn = $res['time'] + $sdvig * 3600;
    $vrn1 = date("H:i/d.m.y", $vrn);
    return $vrn1;
}

// Колличество зарегистрированных пользователей
function kuser()
{
    global $realtime;
    // Общее колличество
    $req = mysql_query("SELECT * FROM `users` ;");
    $total = mysql_num_rows($req);
    // Зарегистрированные за последние сутки
    $req = mysql_query("SELECT * FROM `users` WHERE `datereg`>" . ($realtime - 86400) . ";");
    $res = mysql_num_rows($req);
    if ($res > 0)
        $total = $total . '&nbsp;<font color="#FF0000">+' . $res . '</font>';
    return $total;
}

// Счетчик "Кто в форуме?"
function wfrm($id)
{
    global $realtime;
    $onltime = $realtime - 300;
    $count = 0;
    $qf = @mysql_query("select * from `users` where  lastdate>='" . intval($onltime) . "';");
    while ($arrf = mysql_fetch_array($qf))
    {
        $whf = mysql_query("select * from `count` where name='" . $arrf[name] . "' order by time desc ;");
        while ($whf1 = mysql_fetch_array($whf))
        {
            $whf2[] = $whf1[where];
        }
        $wherf = $whf2[0];
        $whf2 = array();
        $wherf1 = explode(",", $wherf);
        if (empty($id))
        {
            if ($wherf1[0] == "forum")
            {
                $count = $count + 1;
            }
        } else
        {
            if ($wherf == "forum,$id")
            {
                $count = $count + 1;
            }
        }
    }
    return $count;
}

// Статистика загрузок
function dload()
{
    global $realtime;
    $fl = mysql_query("select * from `download` where type='file' ;");
    $countf = mysql_num_rows($fl);
    $old = $realtime - (3 * 24 * 3600);
    $fl1 = mysql_query("select * from `download` where time > '" . $old . "' and type='file' ;");
    $countf1 = mysql_num_rows($fl1);
    $out = $countf;
    if ($countf1 > 0)
    {
        $out = $out . "/<font color='#FF0000'>+$countf1</font>";
    }
    return $out;
}

// Статистика галлереи
// Если вызвать с параметром 1, то будет выдавать только колличество новых картинок
function fgal($mod = 0)
{
    global $realtime;
    $old = $realtime - (3 * 24 * 3600);
    $req = mysql_query("select * from `gallery` where time > '" . $old . "' and type='ft' ;");
    $new = mysql_num_rows($req);
    mysql_free_result($req);
    if ($mod == 0)
    {
        $req = mysql_query("select * from `gallery` where type='ft' ;");
        $total = mysql_num_rows($req);
        mysql_free_result($req);
        $out = $total;
        if ($new > 0)
        {
            $out = $out . "/<font color='#FF0000'>+$new</font>";
        }
    } else
    {
        $out = $new;
    }
    return $out;
}

// Дни рождения
function brth()
{
    global $realtime;
    $mon = date("m", $realtime);
    if (substr($mon, 0, 1) == 0)
    {
        $mon = str_replace("0", "", $mon);
    }
    $day = date("d", $realtime);
    if (substr($day, 0, 1) == 0)
    {
        $day = str_replace("0", "", $day);
    }
    $q = mysql_query("select * from `users` where dayb='" . $day . "' and monthb='" . $mon . "' and preg='1';");
    $count = mysql_num_rows($q);
    return $count;
}

// Статистика библиотеки
function stlib()
{
    global $realtime;
    global $dostlmod;
    $fl = mysql_query("select * from `lib` where type='bk' and moder='1';");
    $countf = mysql_num_rows($fl);
    $old = $realtime - (3 * 24 * 3600);
    $fl1 = mysql_query("select * from `lib` where time > '" . $old . "' and type='bk' and moder='1';");
    $countf1 = mysql_num_rows($fl1);
    $out = $countf;
    if ($countf1 > 0)
    {
        $out = $out . '/<font color="#FF0000">+' . $countf1 . '</font>';
    }
    $fm = @mysql_query("select * from `lib` where type='bk' and moder='0';");
    $countm = @mysql_num_rows($fm);
    if ($dostlmod == '1' && ($countm > 0))
        $out = $out . "/<a href='" . $home . "/library/index.php?act=moder'><font color='#FF0000'> Мод:$countm</font></a>";
    return $out;
}

// Статистика Чата
function wch($id)
{
    global $realtime;
    $onltime = $realtime - 300;
    $count = 0;
    $qf = @mysql_query("select * from `users` where  lastdate>='" . intval($onltime) . "';");
    while ($arrf = mysql_fetch_array($qf))
    {
        $whf = mysql_query("select * from `count` where name='" . $arrf[name] . "' order by time desc ;");
        while ($whf1 = mysql_fetch_array($whf))
        {
            $whf2[] = $whf1[where];
        }
        $wherf = $whf2[0];
        $whf2 = array();
        $wherf1 = explode(",", $wherf);
        if (empty($id))
        {
            if ($wherf1[0] == "chat")
            {
                $count = $count + 1;
            }
        } else
        {
            if ($wherf == "chat,$id")
            {
                $count = $count + 1;
            }
        }
    }
    return $count;
}

// Статистика гостевой
// Если вызвать с параметром 1, то будет выдавать колличество новых в гостевой
// Если вызвать с параметром 2, то будет выдавать колличество новых в Админ-Клубе
function gbook($mod = 0)
{
    global $realtime;
    global $dostmod;
    switch ($mod)
    {
        case 1:
            $req = mysql_query("SELECT * FROM `guest` WHERE `adm`='0' AND `time`>'" . ($realtime - 86400) . "';");
            $count = mysql_num_rows($req);
            break;

        case 2:
            if ($dostmod == 1)
            {
                $req = mysql_query("SELECT * FROM `guest` WHERE `adm`='1' AND `time`>'" . ($realtime - 86400) . "';");
                $count = mysql_num_rows($req);
            }
            break;

        default:
            $req = mysql_query("SELECT * FROM `guest` WHERE `adm`='0' AND `time`>'" . ($realtime - 86400) . "';");
            $count = mysql_num_rows($req);
            if ($dostmod == 1)
            {
                $req = mysql_query("SELECT * FROM `guest` WHERE `adm`='1' AND `time`>'" . ($realtime - 86400) . "';");
                $count = $count . '&nbsp;/&nbsp;<span class="red">' . mysql_num_rows($req) . '</span>';
            }
    }
    return $count;
}

?>