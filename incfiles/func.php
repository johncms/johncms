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

////////////////////////////////////////////////////////////
// Показ различных счетчиков внизу страницы               //
////////////////////////////////////////////////////////////
function counters()
{
    global $headmod;
    $req = mysql_query("SELECT * FROM `cms_counters` WHERE `switch` = '1' ORDER BY `sort` ASC");
    if (mysql_num_rows($req) > 0)
    {
        while ($res = mysql_fetch_array($req))
        {
            $link1 = ($res['mode'] == 1 || $res['mode'] == 2) ? $res['link1'] : $res['link2'];
            $link2 = $res['mode'] == 2 ? $res['link1'] : $res['link2'];
            $count = ($headmod == 'mainpage') ? $link1 : $link2;
            if (!empty($count))
                echo $count;
        }
    }
}

////////////////////////////////////////////////////////////
// Счетчик посетителей онлайн                             //
////////////////////////////////////////////////////////////
function usersonline()
{
    global $realtime;
    global $user_id;
    global $home;
    $ontime = $realtime - 300;
    $qon = mysql_query("SELECT COUNT(*) FROM `users` WHERE `lastdate`>='" . $ontime . "';");
    $qon2 = mysql_result($qon, 0);
    $all = mysql_query("SELECT `id` FROM `count` WHERE `time`>='" . $ontime . "' GROUP BY `ip`, `browser`;");
    $all2 = mysql_num_rows($all);
    return ($user_id ? '<a href="' . $home . '/str/online.php">Онлайн: ' . $qon2 . ' / ' . $all2 . '</a>' : 'Онлайн: ' . $qon2 . ' / ' . $all2);
}

////////////////////////////////////////////////////////////
// Вывод коэффициента сжатия Zlib                         //
////////////////////////////////////////////////////////////
function zipcount()
{
    global $set;
    if ($set['gzip'])
    {
        $Contents = ob_get_contents();
        $gzib_file = strlen($Contents);
        $gzib_file_out = strlen(gzcompress($Contents, 9));
        $gzib_pro = round(100 - (100 / ($gzib_file / $gzib_file_out)), 1);
        echo '<div>Cжатие вкл. (' . $gzib_pro . '%)</div>';
    } else
    {
        echo '<div>Cжатие выкл.</div>';
    }
}

////////////////////////////////////////////////////////////
// Счетсик времени, проведенного на сайте                 //
////////////////////////////////////////////////////////////
function timeonline()
{
    global $realtime;
    global $datauser;
    global $user_id;
    if ($user_id)
        echo '<div>В онлайне: ' . gmdate('H:i:s', ($realtime - $datauser['sestime'])) . '</div>';
}

////////////////////////////////////////////////////////////
// Подсчет колличества переходов по сайту                 //
////////////////////////////////////////////////////////////
function movements()
{
    global $datauser;
    global $user_id;
    global $login;
    if ($user_id)
    {
        $req = mysql_query("SELECT COUNT(*) FROM `count` WHERE `time` > '" . $datauser['sestime'] . "' AND `name` = '" . $login . "'");
        $count = mysql_result($req, 0);
        echo '<div>Переходов: ' . $count . '</div>';
    }
}

////////////////////////////////////////////////////////////
// Счетчик непрочитанных тем на форуме                    //
////////////////////////////////////////////////////////////
function forum_new()
{
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

////////////////////////////////////////////////////////////
// Дата последней новости                                 //
////////////////////////////////////////////////////////////
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

////////////////////////////////////////////////////////////
// Колличество зарегистрированных пользователей           //
////////////////////////////////////////////////////////////
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

////////////////////////////////////////////////////////////
// Счетчик "Кто в форуме?"                                //
////////////////////////////////////////////////////////////
function wfrm($id = '')
{
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

////////////////////////////////////////////////////////////
// Статистика загрузок                                    //
////////////////////////////////////////////////////////////
function dload()
{
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

////////////////////////////////////////////////////////////
// Статистика галлереи                                    //
////////////////////////////////////////////////////////////
// Если вызвать с параметром 1, будет выдавать только колличество новых картинок
function fgal($mod = 0)
{
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

////////////////////////////////////////////////////////////
// Дни рождения                                           //
////////////////////////////////////////////////////////////
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

////////////////////////////////////////////////////////////
// Статистика библиотеки                                  //
////////////////////////////////////////////////////////////
function stlib()
{
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

////////////////////////////////////////////////////////////
// Статистика Чата                                        //
////////////////////////////////////////////////////////////
// Если вызвать с параметром 0,1 то покажет общее число юзеров в Чате
function wch($id = false, $mod = false)
{
    global $realtime;
    $onltime = $realtime - 60;
    if ($mod)
    {
        $where = $id ? 'chat,' . $id : 'chat';
        $res = mysql_query("SELECT `id` FROM `count` WHERE
		`time` > '" . $onltime . "' AND
		`where` LIKE 'chat%'
		GROUP BY `name`;");
    } else
    {
        $where = $id ? 'chat,' . $id : 'chat';
        $res = mysql_query("SELECT `id` FROM `count` WHERE
		`time` > '" . $onltime . "' AND
		`where` = '" . $where . "'
		GROUP BY `name`;");
    }
    $count = mysql_num_rows($res);
    return $count;
}

////////////////////////////////////////////////////////////
// Статистика гостевой                                    //
////////////////////////////////////////////////////////////
// Если вызвать с параметром 1, то будет выдавать колличество новых в гостевой
// Если вызвать с параметром 2, то будет выдавать колличество новых в Админ-Клубе
function gbook($mod = 0)
{
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

////////////////////////////////////////////////////////////
// Обработка ссылок и тэгов BBCODE в тексте               //
////////////////////////////////////////////////////////////
function tags($var = '')
{
    $var = preg_replace_callback('{(?:(\w+://)|www\.|wap\.)[\w-]+(\.[\w-]+)*(?: : \d+)?[^<>"\'()\[\]\s]*(?:(?<! [[:punct:]])|(?<= [-/&+*;]))}xis', "hrefCallback", $var);
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

// Служебная функция парсинга URL
function hrefCallback($p)
{
    $href = !empty($p[1]) ? $p[0] : 'http://' . $p[0];
    return '<a href="' . $href . '">' . $p[0] . '</a>';
}

// Вырезание BBcode тэгов из текста
function notags($var = '')
{
    $var = preg_replace('#\[b\](.*?)\[/b\]#si', '\1', $var);
    $var = preg_replace('#\[i\](.*?)\[/i\]#si', '\1', $var);
    $var = preg_replace('#\[u\](.*?)\[/u\]#si', '\1', $var);
    $var = preg_replace('#\[s\](.*?)\[/s\]#si', '\1', $var);
    $var = preg_replace('#\[red\](.*?)\[/red\]#si', '\1', $var);
    $var = preg_replace('#\[green\](.*?)\[/green\]#si', '\1', $var);
    $var = preg_replace('#\[blue\](.*?)\[/blue\]#si', '\1', $var);
    return $var;
}

////////////////////////////////////////////////////////////
// Маскировка ссылок в тексте                             //
////////////////////////////////////////////////////////////
function antilink($var)
{
    $var = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "[реклама]", $var);
    $var = strtr($var, array(".ru" => "***", ".com" => "***", ".net" => "***", ".org" => "***", ".info" => "***", ".mobi" => "***", ".wen" => "***", ".kmx" => "***", ".h2m" => "***"));
    return $var;
}

////////////////////////////////////////////////////////////
// Транслитерация текста                                  //
////////////////////////////////////////////////////////////
function trans($str)
{
    $str = strtr($str, array('a' => 'а', 'b' => 'б', 'v' => 'в', 'g' => 'г', 'd' => 'д', 'e' => 'е', 'yo' => 'ё', 'zh' => 'ж', 'z' => 'з', 'i' => 'и', 'j' => 'й', 'k' => 'к', 'l' => 'л', 'm' => 'м', 'n' => 'н', 'o' => 'о', 'p' => 'п', 'r' =>
        'р', 's' => 'с', 't' => 'т', 'u' => 'у', 'f' => 'ф', 'h' => 'х', 'c' => 'ц', 'ch' => 'ч', 'w' => 'ш', 'sh' => 'щ', 'q' => 'ъ', 'y' => 'ы', 'x' => 'э', 'yu' => 'ю', 'ya' => 'я', 'A' => 'А', 'B' => 'Б', 'V' => 'В', 'G' => 'Г', 'D' => 'Д', 'E' =>
        'Е', 'YO' => 'Ё', 'ZH' => 'Ж', 'Z' => 'З', 'I' => 'И', 'J' => 'Й', 'K' => 'К', 'L' => 'Л', 'M' => 'М', 'N' => 'Н', 'O' => 'О', 'P' => 'П', 'R' => 'Р', 'S' => 'С', 'T' => 'Т', 'U' => 'У', 'F' => 'Ф', 'H' => 'Х', 'C' => 'Ц', 'CH' => 'Ч', 'W' =>
        'Ш', 'SH' => 'Щ', 'Q' => 'Ъ', 'Y' => 'Ы', 'X' => 'Э', 'YU' => 'Ю', 'YA' => 'Я'));
    return $str;
}

////////////////////////////////////////////////////////////
// Декодирование htmlentities, PHP4совместимый режим      //
////////////////////////////////////////////////////////////
function unhtmlentities($string)
{
    $string = str_replace('&amp;', '&', $string);
    $string = preg_replace('~&#x0*([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
    $string = preg_replace('~&#0*([0-9]+);~e', 'chr(\\1)', $string);
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    return strtr($string, $trans_tbl);
}

////////////////////////////////////////////////////////////
// Функция постраничной навигации                         //
////////////////////////////////////////////////////////////
// За основу взята аналогичная функция от форума SMF2.0   //
////////////////////////////////////////////////////////////
function pagenav($base_url, $start, $max_value, $num_per_page)
{
    $pgcont = 4;
    $pgcont = (int)($pgcont - ($pgcont % 2)) / 2;
    if ($start >= $max_value)
        $start = max(0, (int)$max_value - (((int)$max_value % (int)$num_per_page) == 0 ? $num_per_page : ((int)$max_value % (int)$num_per_page)));
    else
        $start = max(0, (int)$start - ((int)$start % (int)$num_per_page));
    $base_link = '<a class="navpg" href="' . strtr($base_url, array('%' => '%%')) . 'start=%d' . '">%s</a> ';
    $pageindex = $start == 0 ? '' : sprintf($base_link, $start - $num_per_page, '&lt;&lt;');
    if ($start > $num_per_page * $pgcont)
        $pageindex .= sprintf($base_link, 0, '1');
    if ($start > $num_per_page * ($pgcont + 1))
        $pageindex .= '<span style="font-weight: bold;"> ... </span>';
    for ($nCont = $pgcont; $nCont >= 1; $nCont--)
        if ($start >= $num_per_page * $nCont)
        {
            $tmpStart = $start - $num_per_page * $nCont;
            $pageindex .= sprintf($base_link, $tmpStart, $tmpStart / $num_per_page + 1);
        }
    $pageindex .= '[<b>' . ($start / $num_per_page + 1) . '</b>] ';
    $tmpMaxPages = (int)(($max_value - 1) / $num_per_page) * $num_per_page;
    for ($nCont = 1; $nCont <= $pgcont; $nCont++)
        if ($start + $num_per_page * $nCont <= $tmpMaxPages)
        {
            $tmpStart = $start + $num_per_page * $nCont;
            $pageindex .= sprintf($base_link, $tmpStart, $tmpStart / $num_per_page + 1);
        }
    if ($start + $num_per_page * ($pgcont + 1) < $tmpMaxPages)
        $pageindex .= '<span style="font-weight: bold;"> ... </span>';
    if ($start + $num_per_page * $pgcont < $tmpMaxPages)
        $pageindex .= sprintf($base_link, $tmpMaxPages, $tmpMaxPages / $num_per_page + 1);
    if ($start + $num_per_page < $max_value)
    {
        $display_page = ($start + $num_per_page) > $max_value ? $max_value : ($start + $num_per_page);
        $pageindex .= sprintf($base_link, $display_page, '&gt;&gt;');
    }
    return $pageindex;
}

////////////////////////////////////////////////////////////
// Функция пересчета на дни, или часы                     //
////////////////////////////////////////////////////////////
function timecount($var)
{
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

////////////////////////////////////////////////////////////
// Форматирование размера файлов                          //
////////////////////////////////////////////////////////////
function formatsize($size)
{
    if ($size >= 1073741824)
    {
        $size = round($size / 1073741824 * 100) / 100 . ' Gb';
    } elseif ($size >= 1048576)
    {
        $size = round($size / 1048576 * 100) / 100 . ' Mb';
    } elseif ($size >= 1024)
    {
        $size = round($size / 1024 * 100) / 100 . ' Kb';
    } else
    {
        $size = $size . ' b';
    }
    return $size;
}

////////////////////////////////////////////////////////////
// Проверка переменных                                    //
////////////////////////////////////////////////////////////
function check($str)
{
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


#############################################################
## Старые функции, которые постепенно будут удаляться.      #
## НЕ ИСПОЛЬЗУЙТЕ их в своих модулях!!!                     #
#############################################################

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

function smiles($str)
{
    $dir = opendir($_SERVER["DOCUMENT_ROOT"] . "/sm/prost");
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
    $dir = opendir($_SERVER["DOCUMENT_ROOT"] . "/sm/adm");
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
    $dir = opendir($_SERVER["DOCUMENT_ROOT"] . "/sm/cat");
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
        $d = opendir($_SERVER["DOCUMENT_ROOT"] . "/sm/cat/$a[$a1]");
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

function rus_lat($str)
{
    $str = strtr($str, array('а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'j', 'з' => 'z', 'и' => 'i', 'й' => 'i', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => "", 'ы' => 'y', 'ь' => "", 'э' => 'ye', 'ю' => 'yu', 'я' => 'ya'));
    return $str;
}

?>