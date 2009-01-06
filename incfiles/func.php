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

defined('_IN_JOHNCMS') or die('Error:restricted access');

////////////////////////////////////////////////////////////////////////////////
// Статистические функции и счетчики                                          //
////////////////////////////////////////////////////////////////////////////////

function forum_new()
{
    ////////////////////////////////////////////////////////////
    // Счетчик непрочитанных тем на форуме                    //
    ////////////////////////////////////////////////////////////
    global $user_id;
    global $realtime;
    global $dostadm;
    if ($user_id)
    {
        if ($dostadm)
        {
            $req = mysql_query("SELECT COUNT(*) FROM `forum`
			LEFT JOIN `cms_forum_rdm` ON `forum`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $user_id . "'
			WHERE `forum`.`type`='t'
			AND (`cms_forum_rdm`.`topic_id` Is Null
			OR `forum`.`time` > `cms_forum_rdm`.`time`);");
            return mysql_result($req, 0);
        } else
        {
            $req = mysql_query("SELECT COUNT(*) FROM `forum`
			LEFT JOIN `cms_forum_rdm` ON `forum`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $user_id . "'
			WHERE `forum`.`type`='t'
			AND `moder`='1'
			AND `close`!='1'
			AND (`cms_forum_rdm`.`topic_id` Is Null
			OR `forum`.`time` > `cms_forum_rdm`.`time`);");
            return mysql_result($req, 0);
        }
    } else
    {
        return false;
    }
}

function dnews()
{
    ////////////////////////////////////////////////////////////
    // Дата последней новости                                 //
    ////////////////////////////////////////////////////////////
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

function kuser()
{
    ////////////////////////////////////////////////////////////
    // Колличество зарегистрированных пользователей           //
    ////////////////////////////////////////////////////////////
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

function wfrm($id)
{
    ////////////////////////////////////////////////////////////
    // Счетчик "Кто в форуме?"                                //
    ////////////////////////////////////////////////////////////
    global $realtime;
    $onltime = $realtime - 300;
    $count = 0;
    $qf = @mysql_query("select * from `users` where  lastdate>='" . $onltime . "';");
    while ($arrf = mysql_fetch_array($qf))
    {
        $whf = mysql_query("select * from `count` where name='" . $arrf['name'] . "' order by time desc ;");
        while ($whf1 = mysql_fetch_array($whf))
        {
            $whf2[] = $whf1['where'];
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

function dload()
{
    ////////////////////////////////////////////////////////////
    // Статистика загрузок                                    //
    ////////////////////////////////////////////////////////////
    global $realtime;
    $fl = mysql_query("select `id` from `download` where `type`='file' ;");
    $countf = mysql_num_rows($fl);
    $old = $realtime - (3 * 24 * 3600);
    $fl1 = mysql_query("select `id` from `download` where `time` > '" . $old . "' and `type`='file' ;");
    $countf1 = mysql_num_rows($fl1);
    $out = $countf;
    if ($countf1 > 0)
    {
        $out = $out . "/<font color='#FF0000'>+$countf1</font>";
    }
    return $out;
}

function fgal($mod = 0)
{
    ////////////////////////////////////////////////////////////
    // Статистика галлереи                                    //
    ////////////////////////////////////////////////////////////
    // Если вызвать с параметром 1, то будет выдавать только колличество новых картинок
    global $realtime;
    $old = $realtime - (3 * 24 * 3600);
    $req = mysql_query("select `id` from `gallery` where `time` > '" . $old . "' and `type`='ft' ;");
    $new = mysql_num_rows($req);
    mysql_free_result($req);
    if ($mod == 0)
    {
        $req = mysql_query("select `id` from `gallery` where `type`='ft' ;");
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

function brth()
{
    ////////////////////////////////////////////////////////////
    // Дни рождения                                           //
    ////////////////////////////////////////////////////////////
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

function stlib()
{
    ////////////////////////////////////////////////////////////
    // Статистика библиотеки                                  //
    ////////////////////////////////////////////////////////////
    global $realtime;
    global $dostlmod;
    $fl = mysql_query("select `id` from `lib` where `type`='bk' and `moder`='1';");
    $countf = mysql_num_rows($fl);
    $old = $realtime - (3 * 24 * 3600);
    $fl1 = mysql_query("select `id` from `lib` where `time` > '" . $old . "' and `type`='bk' and `moder`='1';");
    $countf1 = mysql_num_rows($fl1);
    $out = $countf;
    if ($countf1 > 0)
    {
        $out = $out . '/<font color="#FF0000">+' . $countf1 . '</font>';
    }
    $fm = @mysql_query("select `id` from `lib` where `type`='bk' and `moder`='0';");
    $countm = @mysql_num_rows($fm);
    if ($dostlmod == '1' && ($countm > 0))
        $out = $out . "/<a href='" . $home . "/library/index.php?act=moder'><font color='#FF0000'> Мод:$countm</font></a>";
    return $out;
}

function wch($id)
{
    ////////////////////////////////////////////////////////////
    // Статистика Чата                                        //
    ////////////////////////////////////////////////////////////
    global $realtime;
    $onltime = $realtime - 300;
    $count = 0;
    $qf = @mysql_query("select `id` from `users` where  `lastdate` >='" . intval($onltime) . "';");
    while ($arrf = mysql_fetch_array($qf))
    {
        $whf = mysql_query("select `id` from `count` where `name`='" . $arrf['name'] . "' order by `time` desc ;");
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

function gbook($mod = 0)
{
    ////////////////////////////////////////////////////////////
    // Статистика гостевой                                    //
    ////////////////////////////////////////////////////////////
    // Если вызвать с параметром 1, то будет выдавать колличество новых в гостевой
    // Если вызвать с параметром 2, то будет выдавать колличество новых в Админ-Клубе
    global $realtime;
    global $dostmod;
    switch ($mod)
    {
        case 1:
            $req = mysql_query("SELECT `id` FROM `guest` WHERE `adm`='0' AND `time`>'" . ($realtime - 86400) . "';");
            $count = mysql_num_rows($req);
            break;

        case 2:
            if ($dostmod == 1)
            {
                $req = mysql_query("SELECT `id` FROM `guest` WHERE `adm`='1' AND `time`>'" . ($realtime - 86400) . "';");
                $count = mysql_num_rows($req);
            }
            break;

        default:
            $req = mysql_query("SELECT `id` FROM `guest` WHERE `adm`='0' AND `time`>'" . ($realtime - 86400) . "';");
            $count = mysql_num_rows($req);
            if ($dostmod == 1)
            {
                $req = mysql_query("SELECT `id` FROM `guest` WHERE `adm`='1' AND `time`>'" . ($realtime - 86400) . "';");
                $count = $count . '&nbsp;/&nbsp;<span class="red">' . mysql_num_rows($req) . '</span>';
            }
    }
    return $count;
}


////////////////////////////////////////////////////////////////////////////////
// Основные функции (используются в большинстве модулей системы)              //
////////////////////////////////////////////////////////////////////////////////

function tags($var = '')
{
    ////////////////////////////////////////////////////////////
    // Обработка ссылок и тэгов BBCODE в тексте               //
    ////////////////////////////////////////////////////////////
    $var = preg_replace_callback('{(?:(\w+://)|www\.|wap\.)[\w-]+(\.[\w-]+)*(?: : \d+)?[^<>"\'()\[\]\s]*(?:(?<! [[:punct:]])|(?<= [-/&+*]))}xis', "hrefCallback", $var);
    $var = preg_replace('#\[b\](.*?)\[/b\]#si', '<span style="font-weight: bold;">\1</span>', $var);
    $var = preg_replace('#\[i\](.*?)\[/i\]#si', '<span style="font-style:italic;">\1</span>', $var);
    $var = preg_replace('#\[u\](.*?)\[/u\]#si', '<span style="text-decoration:underline;">\1</span>', $var);
    $var = preg_replace('#\[s\](.*?)\[/s\]#si', '<span style="text-decoration: line-through;">\1</span>', $var);
    $var = preg_replace('#\[red\](.*?)\[/red\]#si', '<span style="color:red">\1</span>', $var);
    $var = preg_replace('#\[green\](.*?)\[/green\]#si', '<span style="color:green">\1</span>', $var);
    $var = preg_replace('#\[blue\](.*?)\[/blue\]#si', '<span style="color:blue">\1</span>', $var);
    //$var = eregi_replace("\\[l\\]([[:alnum:]_=:/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/;%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='http://\\1'>\\6</a>", $var);
    return $var;
}

function hrefCallback($p)
{
    ////////////////////////////////////////////////////////////
    // Служебная функция парсинга URL                         //
    ////////////////////////////////////////////////////////////
    $name = htmlspecialchars($p[0]);
    $href = !empty($p[1]) ? $name : "http://$name";
    return "<a href=\"$href\">$name</a>";
}

function antilink($var)
{
    ////////////////////////////////////////////////////////////
    // Маскировка ссылок в тексте                             //
    ////////////////////////////////////////////////////////////
    $var = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "[реклама]", $var);
    $var = strtr($var, array(".ru" => "***", ".com" => "***", ".net" => "***", ".org" => "***", ".info" => "***", ".mobi" => "***", ".wen" => "***", ".kmx" => "***", ".h2m" => "***"));
    return $var;
}

function trans($str)
{
    ////////////////////////////////////////////////////////////
    // Транслитерация текста                                  //
    ////////////////////////////////////////////////////////////
    $str = strtr($str, array('a' => 'а', 'b' => 'б', 'v' => 'в', 'g' => 'г', 'd' => 'д', 'e' => 'е', 'yo' => 'ё', 'zh' => 'ж', 'z' => 'з', 'i' => 'и', 'j' => 'й', 'k' => 'к', 'l' => 'л', 'm' => 'м', 'n' => 'н', 'o' => 'о', 'p' => 'п', 'r' =>
        'р', 's' => 'с', 't' => 'т', 'u' => 'у', 'f' => 'ф', 'h' => 'х', 'c' => 'ц', 'ch' => 'ч', 'w' => 'ш', 'sh' => 'щ', 'q' => 'ъ', 'y' => 'ы', 'x' => 'э', 'yu' => 'ю', 'ya' => 'я', 'A' => 'А', 'B' => 'Б', 'V' => 'В', 'G' => 'Г', 'D' => 'Д', 'E' =>
        'Е', 'YO' => 'Ё', 'ZH' => 'Ж', 'Z' => 'З', 'I' => 'И', 'J' => 'Й', 'K' => 'К', 'L' => 'Л', 'M' => 'М', 'N' => 'Н', 'O' => 'О', 'P' => 'П', 'R' => 'Р', 'S' => 'С', 'T' => 'Т', 'U' => 'У', 'F' => 'Ф', 'H' => 'Х', 'C' => 'Ц', 'CH' => 'Ч', 'W' =>
        'Ш', 'SH' => 'Щ', 'Q' => 'Ъ', 'Y' => 'Ы', 'X' => 'Э', 'YU' => 'Ю', 'YA' => 'Я'));
    return $str;
}

function unhtmlentities($string)
{
    ////////////////////////////////////////////////////////////
    // Декодирование htmlentities, PHP4совместимый режим      //
    ////////////////////////////////////////////////////////////
    $string = str_replace('&amp;', '&', $string);
    $string = preg_replace('~&#x0*([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
    $string = preg_replace('~&#0*([0-9]+);~e', 'chr(\\1)', $string);
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    return strtr($string, $trans_tbl);
}

function pagenav($var = array())
{
    ////////////////////////////////////////////////////////////
    // Навигация по страницам                                 //
    ////////////////////////////////////////////////////////////
    $ba = ceil($var['total'] / $var['numpr']);
    $page = ($ba > $var['page']) ? $var['page'] : $ba;
    $start = $page * $var['numpr'] - $var['numpr'];
    $asd = $start - ($var['numpr']);
    $asd2 = $start + ($var['numpr'] * 2);
    echo '<div class="f-pgn">';
    // Ссылка на предыдущую страницу
    if ($start > 0)
        echo '<a href="' . $var['address'] . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
    if ($asd < $var['total'] && $asd > 0)
    {
        echo ' <a href="' . $var['address'] . '&amp;page=1">1</a> .. ';
    }
    $page2 = $ba - $page;
    $pa = ceil($page / 2);
    $paa = ceil($page / 3);
    $pa2 = $page + floor($page2 / 2);
    $paa2 = $page + floor($page2 / 3);
    $paa3 = $page + (floor($page2 / 3) * 2);
    if ($page > 13)
    {
        echo ' <a href="' . $var['address'] . '&amp;page=' . $paa . '">' . $paa . '</a> <a href="' . $var['address'] . '&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="' . $var['address'] . '&amp;page=' . ($paa * 2) . '">' . ($paa *
            2) . '</a> <a href="' . $var['address'] . '&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
    } elseif ($page > 7)
    {
        echo ' <a href="' . $var['address'] . '&amp;page=' . $pa . '">' . $pa . '</a> <a href="' . $var['address'] . '&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
    }
    for ($i = $asd; $i < $asd2; )
    {
        if ($i < $var['total'] && $i >= 0)
        {
            $ii = floor(1 + $i / $var['numpr']);
            if ($start == $i)
            {
                echo " <b>$ii</b>";
            } else
            {
                echo ' <a href="' . $var['address'] . '&amp;page=' . $ii . '">' . $ii . '</a> ';
            }
        }
        $i = $i + $var['numpr'];
    }
    if ($page2 > 12)
    {
        echo ' .. <a href="' . $var['address'] . '&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="' . $var['address'] . '&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="' . $var['address'] . '&amp;page=' . ($paa3) . '">' . ($paa3) .
            '</a> <a href="' . $var['address'] . '&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
    } elseif ($page2 > 6)
    {
        echo ' .. <a href="' . $var['address'] . '&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="' . $var['address'] . '&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
    }
    if ($asd2 < $var['total'])
    {
        echo ' .. <a href="' . $var['address'] . '&amp;page=' . $ba . '">' . $ba . '</a>';
    }
    // Ссылка на следующую страницу
    if ($var['total'] > $start + $var['numpr'])
        echo ' <a href="' . $var['address'] . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
    echo '</div>';
}

function timecount($var)
{
    ////////////////////////////////////////////////////////////
    // Функция пересчета на дни, или часы                     //
    ////////////////////////////////////////////////////////////
    $str = '';
    if ($var < 0)
        $var = 0;
    $day = ceil($var / 86400);
    if ($var > 2592000)
    {
        $str = 'До отмены';
    } elseif ($var > 345600)
    {
        $str = $day . ' дней';
    } elseif ($var >= 172800)
    {
        $str = $day . ' дня';
    } elseif ($var >= 86400)
    {
        $str = '1 день';
    } else
    {
        $str = gmdate('H:i:s', round($var));
    }
    return $str;
}

////////////////////////////////////////////////////////////////////////////////
// Старые функции, которые постепенно будут удаляться.                        //
// НЕ ИСПОЛЬЗУЙТЕ их в своих модулях!!!                                       //
////////////////////////////////////////////////////////////////////////////////

function texttolink($str)
{
    $str = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)", "<a href='\\1\\3'>\\3</a>", $str);
    return $str;
}

function provcat($catalog)
{
    $cat1 = mysql_query("select * from `download` where type = 'cat' and id = '" . $catalog . "';");
    $cat2 = mysql_num_rows($cat1);
    $adrdir = mysql_fetch_array($cat1);
    if (($cat2 == 0) || (!is_dir("$adrdir[adres]/$adrdir[name]")))
    {
        echo "Ошибка при выборе категории<br/><a href='?'>К категориям</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
}
function provupl($catalog)
{
    $cat1 = mysql_query("select * from `upload` where type = 'cat' and id = '" . $catalog . "';");
    $cat2 = mysql_num_rows($cat1);
    $adrdir = mysql_fetch_array($cat1);
    if (($cat2 == 0) || (!is_dir("$adrdir[adres]/$adrdir[name]")))
    {
        echo "Ошибка при выборе категории<br/><a href='?'>К категориям</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
}

function deletcat($catalog)
{
    $dir = opendir($catalog);
    while (($file = readdir($dir)))
    {
        if (is_file($catalog . "/" . $file))
        {
            unlink($catalog . "/" . $file);
        } else
            if (is_dir($catalog . "/" . $file) && ($file != ".") && ($file != ".."))
            {
                deletcat($catalog . "/" . $file);
            }
    }
    closedir($dir);
    rmdir($catalog);
}

function format($name)
{
    $f1 = strrpos($name, ".");
    $f2 = substr($name, $f1 + 1, 999);
    $fname = strtolower($f2);
    return $fname;
}


// Проверка переменных
function check($str)
{
    if (get_magic_quotes_gpc())
        $str = stripslashes($str);
    $str = htmlentities($str, ENT_QUOTES, 'UTF-8');
    $str = str_replace("\'", "&#39;", $str);
    $str = str_replace("\r\n", "<br/>", $str);
    $str = strtr($str, array(chr("0") => "", chr("1") => "", chr("2") => "", chr("3") => "", chr("4") => "", chr("5") => "", chr("6") => "", chr("7") => "", chr("8") => "", chr("9") => "", chr("10") => "", chr("11") => "", chr("12") => "", chr
        ("13") => "", chr("14") => "", chr("15") => "", chr("16") => "", chr("17") => "", chr("18") => "", chr("19") => "", chr("20") => "", chr("21") => "", chr("22") => "", chr("23") => "", chr("24") => "", chr("25") => "", chr("26") => "", chr("27") =>
        "", chr("28") => "", chr("29") => "", chr("30") => "", chr("31") => ""));
    $str = str_replace('\\', "&#92;", $str);
    $str = str_replace("|", "I", $str);
    $str = str_replace("||", "I", $str);
    $str = str_replace("/\\\$/", "&#36;", $str);
    $str = str_replace("[l]http://", "[l]", $str);
    $str = str_replace("[l] http://", "[l]", $str);
    $str = mysql_real_escape_string($str);
    return $str;
}

function smiles($str)
{
    $dir = opendir("../sm/prost");
    while ($file = readdir($dir))
    {
        if (ereg(".gif$", "$file"))
        {
            $file2 = $file;
            $file2 = str_replace(".gif", "", $file2);
            $str = str_replace(":$file2", "<img src=\"../sm/prost/$file2.gif\" alt=\"\" />", $str);
        }
    }
    closedir($dir);
    return $str;
}

function smilesadm($str)
{
    $dir = opendir("../sm/adm");
    while ($file = readdir($dir))
    {
        if (ereg(".gif$", "$file"))
        {
            $file2 = $file;
            $file2 = str_replace(".gif", "", $file2);
            $trfile = trans($file2);
            $str = str_replace(":$file2:", "<img src=\"../sm/adm/$file2.gif\" alt=\"\" />", $str);
            $str = str_replace(":$trfile:", "<img src=\"../sm/adm/$file2.gif\" alt=\"\" />", $str);
        }
    }
    closedir($dir);
    return $str;
}

function smilescat($str)
{
    $dir = opendir("../sm/cat");
    while ($file = readdir($dir))
    {
        if (($file != ".") && ($file != "..") && ($file != ".htaccess") && ($file != "index.php"))
        {
            $a[] = $file;
        }
    }
    closedir($dir);
    $total = count($a);
    for ($a1 = 0; $a1 < $total; $a1++)
    {
        $d = opendir("../sm/cat/$a[$a1]");
        while ($k = readdir($d))
        {
            if (ereg(".gif$", "$k"))
            {
                $file2 = $k;
                $file2 = str_replace(".gif", "", $file2);
                $trfile = trans($file2);
                $str = str_replace(":$file2:", "<img src=\"../sm/cat/$a[$a1]/$file2.gif\" alt=\"\" />", $str);
                $str = str_replace(":$trfile:", "<img src=\"../sm/cat/$a[$a1]/$file2.gif\" alt=\"\" />", $str);
            }
        }
        closedir($d);
    }
    return $str;
}

function navigate($adr_str, $itogo, $kol_na_str, $begin, $num_str)
{
    $ba = ceil($itogo / $kol_na_str);
    $asd = $begin - ($kol_na_str);
    $asd2 = $begin + ($kol_na_str * 2);
    if ($asd < $itogo && $asd > 0)
    {
        echo ' <a href="' . $adr_str . '&amp;page=1&amp;">1</a> .. ';
    }
    $page2 = $ba - $num_str;
    $pa = ceil($num_str / 2);
    $paa = ceil($num_str / 3);
    $pa2 = $num_str + floor($page2 / 2);
    $paa2 = $num_str + floor($page2 / 3);
    $paa3 = $num_str + (floor($page2 / 3) * 2);
    if ($num_str > 13)
    {
        echo ' <a href="' . $adr_str . '&amp;page=' . $paa . '">' . $paa . '</a> <a href="' . $adr_str . '&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="' . $adr_str . '&amp;page=' . ($paa * 2) . '">' . ($paa * 2) .
            '</a> <a href="' . $adr_str . '&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
    } elseif ($num_str > 7)
    {
        echo ' <a href="' . $adr_str . '&amp;page=' . $pa . '">' . $pa . '</a> <a href="' . $adr_str . '&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
    }
    for ($i = $asd; $i < $asd2; )
    {
        if ($i < $itogo && $i >= 0)
        {
            $ii = floor(1 + $i / $kol_na_str);

            if ($begin == $i)
            {
                echo " <b>$ii</b>";
            } else
            {
                echo ' <a href="' . $adr_str . '&amp;page=' . $ii . '">' . $ii . '</a> ';
            }
        }
        $i = $i + $kol_na_str;
    }
    if ($page2 > 12)
    {
        echo ' .. <a href="' . $adr_str . '&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="' . $adr_str . '&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="' . $adr_str . '&amp;page=' . ($paa3) . '">' . ($paa3) .
            '</a> <a href="' . $adr_str . '&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
    } elseif ($page2 > 6)
    {
        echo ' .. <a href="' . $adr_str . '&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="' . $adr_str . '&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
    }
    if ($asd2 < $itogo)
    {
        echo ' .. <a href="' . $adr_str . '&amp;page=' . $ba . '">' . $ba . '</a>';
    }

}

function rus_lat($str)
{
    $str = strtr($str, array('а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'j', 'з' => 'z', 'и' => 'i', 'й' => 'i', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => "", 'ы' => 'y', 'ь' => "", 'э' => 'ye', 'ю' => 'yu', 'я' => 'ya'));
    return $str;
}

?>