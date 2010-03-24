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

defined('_IN_JOHNCMS') or die('Error: restricted access');

function counters() {
    ////////////////////////////////////////////////////////////
    // Показ различных счетчиков внизу страницы               //
    ////////////////////////////////////////////////////////////
    global $headmod;
    $req = mysql_query("SELECT * FROM `cms_counters` WHERE `switch` = '1' ORDER BY `sort` ASC");
    if (mysql_num_rows($req) > 0) {
        while ($res = mysql_fetch_array($req)) {
            $link1 = ($res['mode'] == 1 || $res['mode'] == 2) ? $res['link1'] : $res['link2'];
            $link2 = $res['mode'] == 2 ? $res['link1'] : $res['link2'];
            $count = ($headmod == 'mainpage') ? $link1 : $link2;
            if (!empty ($count))
                echo $count;
        }
    }
}

function usersonline() {
    ////////////////////////////////////////////////////////////
    // Счетчик посетителей онлайн                             //
    ////////////////////////////////////////////////////////////
    global $realtime, $user_id, $home;
    $users = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `lastdate` > '" . ($realtime - 300) . "'"), 0);
    $guests = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_guests` WHERE `lastdate` > '" . ($realtime - 300) . "'"), 0);
    return ($user_id ? '<a href="' . $home . '/str/online.php">Онлайн: ' . $users . ' / ' . $guests . '</a>' : 'Онлайн: ' . $users . ' / ' . $guests);
}

function zipcount() {
    ////////////////////////////////////////////////////////////
    // Вывод коэффициента сжатия Zlib                         //
    ////////////////////////////////////////////////////////////
    global $set;
    if ($set['gzip']) {
        $Contents = ob_get_contents();
        $gzib_file = strlen($Contents);
        $gzib_file_out = strlen(gzcompress($Contents, 9));
        $gzib_pro = round(100 - (100 / ($gzib_file / $gzib_file_out)), 1);
        echo '<div>Cжатие вкл. (' . $gzib_pro . '%)</div>';
    }
    else {
        echo '<div>Cжатие выкл.</div>';
    }
}

function timeonline() {
    ////////////////////////////////////////////////////////////
    // Счетсик времени, проведенного на сайте                 //
    ////////////////////////////////////////////////////////////
    global $realtime, $datauser, $user_id;
    if ($user_id)
        echo '<div>В онлайне: ' . gmdate('H:i:s', ($realtime - $datauser['sestime'])) . '</div>';
}

function forum_new($mod = 0) {
    ////////////////////////////////////////////////////////////
    // Счетчик непрочитанных тем на форуме                    //
    ////////////////////////////////////////////////////////////
    // $mod = 0   Возвращает число непрочитанных тем          //
    // $mod = 1   Выводит ссылки на непрочитанное             //
    ////////////////////////////////////////////////////////////
    global $user_id, $rights;
    if ($user_id) {
        $req = mysql_query("SELECT COUNT(*) FROM `forum`
        LEFT JOIN `cms_forum_rdm` ON `forum`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $user_id . "'
        WHERE `forum`.`type`='t'" . ($rights >= 7 ? "" : " AND `forum`.`close` != '1'") . "
        AND (`cms_forum_rdm`.`topic_id` Is Null
        OR `forum`.`time` > `cms_forum_rdm`.`time`)");
        $total = mysql_result($req, 0);
        if ($mod)
            echo '<p><a href="index.php?act=new">Непрочитанное</a>&nbsp;' . ($total ? '<span class="red">(<b>' . $total . '</b>)</span>' : '') . '</p>';
        else
            return $total;
    }
    else {
        if ($mod)
            echo '<p><a href="index.php?act=new">Последние 10 тем</a></p>';
        else
            return false;
    }
}

function dnews() {
    ////////////////////////////////////////////////////////////
    // Дата последней новости                                 //
    ////////////////////////////////////////////////////////////
    global $set_user;
    $req = mysql_query("SELECT `time` FROM `news` ORDER BY `time` DESC LIMIT 1");
    if (mysql_num_rows($req)) {
        $res = mysql_fetch_array($req);
        return date("H:i/d.m.y", $res['time'] + $set_user['sdvig'] * 3600);
    }
    else {
        return false;
    }
}

function kuser() {
    ////////////////////////////////////////////////////////////
    // Колличество зарегистрированных пользователей           //
    ////////////////////////////////////////////////////////////
    global $realtime;
    $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `users`"), 0);
    $res = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `datereg` > '" . ($realtime - 86400) . "'"), 0);
    if ($res > 0)
        $total .= '&nbsp;<span class="red">+' . $res . '</span>';
    return $total;
}

function wfrm() {
    ////////////////////////////////////////////////////////////
    // Статистика Форума                                      //
    ////////////////////////////////////////////////////////////
    global $user_id, $rights, $home;
    $total_thm = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't'" . ($rights >= 7 ? "" : " AND `close` != '1'")), 0);
    $total_msg = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm'" . ($rights >= 7 ? "" : " AND `close` != '1'")), 0);
    $out = $total_thm . '&nbsp;/&nbsp;' . $total_msg . '';
    if ($user_id) {
        $new = forum_new();
        if ($new)
            $out .= '&nbsp;/&nbsp;<span class="red"><a href="' . $home . '/forum/index.php?act=new">+' . $new . '</a></span>';
    }
    return $out;
}

function dload() {
    ////////////////////////////////////////////////////////////
    // Статистика загрузок                                    //
    ////////////////////////////////////////////////////////////
    global $realtime;
    $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `download` WHERE `type` = 'file'"), 0);
    $old = $realtime - (3 * 24 * 3600);
    $new = mysql_result(mysql_query("SELECT COUNT(*) FROM `download` WHERE `time` > '" . $old . "' AND `type` = 'file'"), 0);
    if ($new > 0)
        $total .= '&nbsp;/&nbsp;<span class="red"><a href="/download/?act=new">+' . $new . '</a></span>';
    return $total;
}

function fgal($mod = 0) {
    ////////////////////////////////////////////////////////////
    // Статистика галлереи                                    //
    ////////////////////////////////////////////////////////////
    // Если вызвать с параметром 1, будет выдавать только колличество новых картинок
    global $realtime;
    $old = $realtime - (3 * 24 * 3600);
    $new = mysql_result(mysql_query("SELECT COUNT(*) FROM `gallery` WHERE `time` > '" . $old . "' AND `type` = 'ft'"), 0);
    if ($mod == 0) {
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `gallery` WHERE `type` = 'ft'"), 0);
        $out = $total;
        if ($new > 0)
            $out .= '&nbsp;/&nbsp;<span class="red"><a href="/gallery/index.php?act=new">+' . $new . '</a></span>';
    }
    else {
        $out = $new;
    }
    return $out;
}

function stlib() {
    ////////////////////////////////////////////////////////////
    // Статистика библиотеки                                  //
    ////////////////////////////////////////////////////////////
    global $realtime, $rights;
    $countf = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = '1'"), 0);
    $old = $realtime - (3 * 24 * 3600);
    $countf1 = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `time` > '" . $old . "' AND `type` = 'bk' AND `moder` = '1'"), 0);
    $out = $countf;
    if ($countf1 > 0)
        $out = $out . '&nbsp;/&nbsp;<span class="red"><a href="/library/index.php?act=new">+' . $countf1 . '</a></span>';
    $countm = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = '0'"), 0);
    if (($rights == 5 || $rights >= 6) && $countm > 0)
        $out = $out . "/<a href='" . $home . "/library/index.php?act=moder'><font color='#FF0000'> Мод:$countm</font></a>";
    return $out;
}

function wch($id = false, $mod = false) {
    ////////////////////////////////////////////////////////////
    // Статистика Чата                                        //
    ////////////////////////////////////////////////////////////
    //TODO: Написать функцию статистики Чата
    return 0;
}

function gbook($mod = 0) {
    ////////////////////////////////////////////////////////////
    // Статистика гостевой                                    //
    ////////////////////////////////////////////////////////////
    // Если вызвать с параметром 1, то будет выдавать колличество новых в гостевой
    // Если вызвать с параметром 2, то будет выдавать колличество новых в Админ-Клубе
    global $realtime, $rights;
    switch ($mod) {
        case 1 :
            $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `guest` WHERE `adm`='0' AND `time` > '" . ($realtime - 86400) . "'"), 0);
            break;

        case 2 :
            if ($rights >= 1)
                $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `guest` WHERE `adm`='1' AND `time` > '" . ($realtime - 86400) . "'"), 0);
            break;

        default :
            $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `guest` WHERE `adm`='0' AND `time` > '" . ($realtime - 86400) . "'"), 0);
            if ($rights >= 1) {
                $req = mysql_query("SELECT COUNT(*) FROM `guest` WHERE `adm`='1' AND `time`>'" . ($realtime - 86400) . "'");
                $count = $count . '&nbsp;/&nbsp;<span class="red"><a href="str/guest.php?act=ga&amp;do=set">' . mysql_result($req, 0) . '</a></span>';
            }
    }
    return $count;
}

function tags($var = '') {
    ////////////////////////////////////////////////////////////
    // Обработка ссылок и тэгов BBCODE в тексте               //
    ////////////////////////////////////////////////////////////
    $var = preg_replace(array('#\[php\](.*?)\[\/php\]#se'), array("''.highlight('$1').''"), str_replace("]\n", "]", $var));
    $var = preg_replace('#\[b\](.*?)\[/b\]#si', '<span style="font-weight: bold;">\1</span>', $var);
    $var = preg_replace('#\[i\](.*?)\[/i\]#si', '<span style="font-style:italic;">\1</span>', $var);
    $var = preg_replace('#\[u\](.*?)\[/u\]#si', '<span style="text-decoration:underline;">\1</span>', $var);
    $var = preg_replace('#\[s\](.*?)\[/s\]#si', '<span style="text-decoration: line-through;">\1</span>', $var);
    $var = preg_replace('#\[red\](.*?)\[/red\]#si', '<span style="color:red">\1</span>', $var);
    $var = preg_replace('#\[green\](.*?)\[/green\]#si', '<span style="color:green">\1</span>', $var);
    $var = preg_replace('#\[blue\](.*?)\[/blue\]#si', '<span style="color:blue">\1</span>', $var);
    $var = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $var);
    $var = preg_replace_callback('~\\[url=(https?://.+?)\\](.+?)\\[/url\\]|(https?://(www.)?[0-9a-z\.-]+\.[0-9a-z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~', 'url_replace', $var);
    return $var;
}
function highlight($php) {
    // Служебная функция подсветки PHP кода (прислал FlySelf)
    $php = strtr($php, array('<br />' => '', '\\' => 'slash_JOHNCMS'));
    $php = html_entity_decode(trim($php), ENT_QUOTES, 'UTF-8');
    $php = substr($php, 0, 2) != "<?" ? $php = "<?php\n" . $php . "\n?>" : $php;
    $php = highlight_string(stripslashes($php), true);
    $php = strtr($php, array('slash_JOHNCMS' => '&#92;', ':' => '&#58;', '[' => '&#91;'));
    return '<div class="phpcode">' . $php . '</div>';
}
function url_replace($m) {
    // Служебная функция парсинга URL (прислал FlySelf)
    if (!isset ($m[3]))
        return '<a href="' . $m[1] . '">' . $m[2] . '</a>';
    else
        return '<a href="' . $m[3] . '">' . $m[3] . '</a>';
}

function notags($var = '') {
    ////////////////////////////////////////////////////////////
    // Вырезание BBcode тэгов из текста                       //
    ////////////////////////////////////////////////////////////
    $var = strtr($var, array('[green]' => '', '[/green]' => '', '[red]' => '', '[/red]' => '', '[blue]' => '', '[/blue]' => '', '[b]' => '', '[/b]' => '', '[i]' => '', '[/i]' => '', '[u]' => '', '[/u]' => '', '[s]' => '', '[/s]' => '', '[c]' => '', '[/c]' => ''));
    return $var;
}

function antilink($var) {
    ////////////////////////////////////////////////////////////
    // Маскировка ссылок в тексте                             //
    ////////////////////////////////////////////////////////////
    $var = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "[реклама]", $var);
    $var = strtr($var, array(".ru" => "***", ".com" => "***", ".net" => "***", ".org" => "***", ".info" => "***", ".mobi" => "***", ".wen" => "***", ".kmx" => "***", ".h2m" => "***"));
    return $var;
}

function trans($str) {
    ////////////////////////////////////////////////////////////
    // Транслитерация текста                                  //
    ////////////////////////////////////////////////////////////
    $str = strtr($str, array('a' => 'а', 'b' => 'б', 'v' => 'в', 'g' => 'г', 'd' => 'д', 'e' => 'е', 'yo' => 'ё', 'zh' => 'ж', 'z' => 'з', 'i' => 'и', 'j' => 'й', 'k' => 'к', 'l' => 'л', 'm' => 'м', 'n' => 'н', 'o' => 'о', 'p' => 'п', 'r' => 'р', 's' => 'с', 't' => 'т', 'u' => 'у', 'f' => 'ф', 'h' => 'х', 'c' => 'ц', 'ch' => 'ч', 'w' => 'ш', 'sh' => 'щ', 'q' => 'ъ', 'y' => 'ы', 'x' => 'э', 'yu' => 'ю', 'ya' => 'я', 'A' => 'А', 'B' => 'Б', 'V' => 'В', 'G' => 'Г', 'D' => 'Д', 'E' => 'Е', 'YO' => 'Ё', 'ZH' => 'Ж', 'Z' => 'З', 'I' => 'И', 'J' => 'Й', 'K' => 'К', 'L' => 'Л', 'M' => 'М', 'N' => 'Н', 'O' => 'О', 'P' => 'П', 'R' => 'Р', 'S' => 'С', 'T' => 'Т', 'U' => 'У', 'F' => 'Ф', 'H' => 'Х', 'C' => 'Ц', 'CH' => 'Ч', 'W' => 'Ш', 'SH' => 'Щ', 'Q' => 'Ъ', 'Y' => 'Ы', 'X' => 'Э', 'YU' => 'Ю', 'YA' => 'Я'));
    return $str;
}

function unhtmlentities($string) {
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

function pagenav($base_url, $start, $max_value, $num_per_page) {
    ////////////////////////////////////////////////////////////
    // Функция постраничной навигации                         //
    ////////////////////////////////////////////////////////////
    // За основу взята аналогичная функция от форума SMF2.0   //
    ////////////////////////////////////////////////////////////
    $pgcont = 4;
    $pgcont = (int) ($pgcont - ($pgcont % 2)) / 2;
    if ($start >= $max_value)
        $start = max(0, (int) $max_value - (((int) $max_value % (int) $num_per_page) == 0 ? $num_per_page : ((int) $max_value % (int) $num_per_page)));
    else
        $start = max(0, (int) $start - ((int) $start % (int) $num_per_page));
    $base_link = '<a class="navpg" href="' . strtr($base_url, array('%' => '%%')) . 'start=%d' . '">%s</a> ';
    $pageindex = $start == 0 ? '' : sprintf($base_link, $start - $num_per_page, '&lt;&lt;');
    if ($start > $num_per_page * $pgcont)
        $pageindex .= sprintf($base_link, 0, '1');
    if ($start > $num_per_page * ($pgcont + 1))
        $pageindex .= '<span style="font-weight: bold;"> ... </span>';
    for ($nCont = $pgcont; $nCont >= 1; $nCont--)
        if ($start >= $num_per_page * $nCont) {
            $tmpStart = $start - $num_per_page * $nCont;
            $pageindex .= sprintf($base_link, $tmpStart, $tmpStart / $num_per_page + 1);
        }
        $pageindex .= '[<b>' . ($start / $num_per_page + 1) . '</b>] ';
    $tmpMaxPages = (int) (($max_value - 1) / $num_per_page) * $num_per_page;
    for ($nCont = 1; $nCont <= $pgcont; $nCont++)
        if ($start + $num_per_page * $nCont <= $tmpMaxPages) {
            $tmpStart = $start + $num_per_page * $nCont;
            $pageindex .= sprintf($base_link, $tmpStart, $tmpStart / $num_per_page + 1);
        }
        if ($start + $num_per_page * ($pgcont + 1) < $tmpMaxPages)
            $pageindex .= '<span style="font-weight: bold;"> ... </span>';
        if ($start + $num_per_page * $pgcont < $tmpMaxPages)
            $pageindex .= sprintf($base_link, $tmpMaxPages, $tmpMaxPages / $num_per_page + 1);
        if ($start + $num_per_page < $max_value) {
            $display_page = ($start + $num_per_page) > $max_value ? $max_value : ($start + $num_per_page);
            $pageindex .= sprintf($base_link, $display_page, '&gt;&gt;');
        }
        return $pageindex;
}

function timecount($var) {
    ////////////////////////////////////////////////////////////
    // Функция пересчета на дни, или часы                     //
    ////////////////////////////////////////////////////////////
    $str = '';
    if ($var < 0)
        $var = 0;
    $day = ceil($var / 86400);
    if ($var > 345600) {
        $str = $day . ' дней';
    }
    elseif ($var >= 172800) {
        $str = $day . ' дня';
    }
    elseif ($var >= 86400) {
        $str = '1 день';
    }
    else {
        $str = gmdate('G:i:s', $var);
    }
    return $str;
}

function formatsize($size) {
    ////////////////////////////////////////////////////////////
    // Форматирование размера файлов                          //
    ////////////////////////////////////////////////////////////
    if ($size >= 1073741824) {
        $size = round($size / 1073741824 * 100) / 100 . ' Gb';
    }
    elseif ($size >= 1048576) {
        $size = round($size / 1048576 * 100) / 100 . ' Mb';
    }
    elseif ($size >= 1024) {
        $size = round($size / 1024 * 100) / 100 . ' Kb';
    }
    else {
        $size = $size . ' b';
    }
    return $size;
}

function check($str) {
    ////////////////////////////////////////////////////////////
    // Проверка переменных                                    //
    ////////////////////////////////////////////////////////////
    $str = htmlentities(trim($str), ENT_QUOTES, 'UTF-8');
    $str = nl2br($str);
    $str = strtr($str, array(chr(0) => '', chr(1) => '', chr(2) => '', chr(3) => '', chr(4) => '', chr(5) => '', chr(6) => '', chr(7) => '', chr(8) => '', chr(9) => '', chr(10) => '', chr(11) => '', chr(12) => '', chr(13) => '', chr(14) => '', chr(15) => '', chr(16) => '', chr(17) => '', chr(18) => '', chr(19) => '', chr(20) => '', chr(21) => '', chr(22) => '', chr(23) => '', chr(24) => '', chr(25) => '', chr(26) => '', chr(27) => '', chr(28) => '', chr(29) => '', chr(30) => '', chr(31) => ''));
    $str = str_replace("\'", "&#39;", $str);
    $str = str_replace('\\', "&#92;", $str);
    $str = str_replace("|", "I", $str);
    $str = str_replace("||", "I", $str);
    $str = str_replace("/\\\$/", "&#36;", $str);
    $str = mysql_real_escape_string($str);
    return $str;
}

function checkout($str, $br = 0, $tags = 0) {
    ////////////////////////////////////////////////////////////
    // Обработка текстов перед выводом на экран               //
    ////////////////////////////////////////////////////////////
    // $br=1   с обработкой переносов строк                   //
    // $br=2   подстановка пробела, вместо переноса           //
    // $tags=1 с обработкой тэгов                             //
    // $tags=2 с вырезанием тэгов                             //
    ////////////////////////////////////////////////////////////
    $str = htmlentities($str, ENT_QUOTES, 'UTF-8');
    if ($br == 1)
        $str = nl2br($str);
    elseif ($br == 2)
        $str = str_replace("\r\n", ' ', $str);
    if ($tags == 1)
        $str = tags($str);
    elseif ($tags == 2)
        $str = notags($str);
    $str = strtr($str, array(chr(0) => '', chr(1) => '', chr(2) => '', chr(3) => '', chr(4) => '', chr(5) => '', chr(6) => '', chr(7) => '', chr(8) => '', chr(9) => '', chr(10) => '', chr(11) => '', chr(12) => '', chr(13) => '', chr(14) => '', chr(15) => '', chr(16) => '', chr(17) => '', chr(18) => '', chr(19) => '', chr(20) => '', chr(21) => '', chr(22) => '', chr(23) => '', chr(24) => '', chr(25) => '', chr(26) => '', chr(27) => '', chr(28) => '', chr(29) => '', chr(30) => '', chr(31) => ''));
    return $str;
}

function smileys($str, $adm = 0) {
    ////////////////////////////////////////////////////////////
    // Обработка смайлов                                      //
    ////////////////////////////////////////////////////////////
    // $adm=1 покажет и обычные и Админские смайлы            //
    // $adm=2 пересоздаст кэш смайлов                         //
    ////////////////////////////////////////////////////////////
    global $rootpath;
    // Записываем КЭШ смайлов
    if ($adm == 2) {
        // Обрабатываем простые смайлы
        $array1 = array();
        $path = $rootpath . 'smileys/simply/';
        $dir = opendir($path);
        while ($file = readdir($dir)) {
            $name = explode(".", $file);
            if ($name[1] == 'gif' || $name[1] == 'jpg' || $name[1] == 'png') {
                $array1[':' . $name[0]] = '<img src="' . $path . $file . '" alt="" />';
                ++$count;
            }
        }
        closedir($dir);
        // Обрабатываем Админские смайлы
        $array2 = array();
        $array3 = array();
        $path = $rootpath . 'smileys/admin/';
        $dir = opendir($path);
        while ($file = readdir($dir)) {
            $name = explode(".", $file);
            if ($name[1] == 'gif' || $name[1] == 'jpg' || $name[1] == 'png') {
                $array2[':' . trans($name[0]) . ':'] = '<img src="' . $path . $file . '" alt="" />';
                $array3[':' . $name[0] . ':'] = '<img src="' . $path . $file . '" alt="" />';
                ++$count;
            }
        }
        // Обрабатываем смайлы в каталогах
        $array4 = array();
        $array5 = array();
        $cat = glob($rootpath . 'smileys/user/*', GLOB_ONLYDIR);
        $total = count($cat);
        for ($i = 0; $i < $total; $i++) {
            $dir = opendir($cat[$i]);
            while ($file = readdir($dir)) {
                $name = explode(".", $file);
                if ($name[1] == 'gif' || $name[1] == 'jpg' || $name[1] == 'png') {
                    $array4[':' . trans($name[0]) . ':'] = '<img src="' . $cat[$i] . '/' . $file . '" alt="" />';
                    $array5[':' . $name[0] . ':'] = '<img src="' . $cat[$i] . '/' . $file . '" alt="" />';
                    ++$count;
                }
            }
            closedir($dir);
        }
        $smileys = serialize(array_merge($array1, $array4, $array5));
        $smileys_adm = serialize(array_merge($array2, $array3));
        // Записываем в файл Кэша
        if ($fp = fopen($rootpath . 'cache/smileys_cache.dat', 'w')) {
            fputs($fp, $smileys . "\r\n" . $smileys_adm);
            fclose($fp);
            return $count;
        }
        else {
            return false;
        }
    }
    else {
        // Выдаем кэшированные смайлы
        if (file_exists($rootpath . 'cache/smileys_cache.dat')) {
            $file = file($rootpath . 'cache/smileys_cache.dat');
            $smileys = unserialize($file[0]);
            if ($adm)
                $smileys = array_merge($smileys, unserialize($file[1]));
            return strtr($str, $smileys);
        }
        else {
            return $str;
        }
    }
}

function display_error($error = false) {
    ////////////////////////////////////////////////////////////
    // Сообщения об ошибках                                   //
    ////////////////////////////////////////////////////////////
    if ($error) {
        $out = '<div class="rmenu"><p>ОШИБКА!';
        if (is_array($error)) {
            foreach ($error as $val)
                $out .= '<div>' . $val . '</div>';
        }
        else {
            $out .= '<br />' . $error;
        }
        $out .= '</p></div>';
        return $out;
    }
    else {
        return false;
    }
}

function rus_lat($str) {
    ////////////////////////////////////////////////////////////
    // Транслитерация с Русского в латиницу                   //
    ////////////////////////////////////////////////////////////
    $str = strtr($str, array('а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'j', 'з' => 'z', 'и' => 'i', 'й' => 'i', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => "", 'ы' => 'y', 'ь' => "", 'э' => 'ye', 'ю' => 'yu', 'я' => 'ya'));
    return $str;
}

function show_user($user = array(), $status = 0, $ip = 0, $str = '', $text = '', $sub = '') {
////////////////////////////////////////////////////////////
// Отображение пользователей                              //
////////////////////////////////////////////////////////////
// $user (array)     - массив запроса в таблицу `users`   //
// $status (boolean) - показать статус                    //
// $ip (int)         - отображение IP и UserAgent         //
//                     0 - не показывать                  //
//                     1 - показать                       //
//                     2 - показать ссылку на IP поиск    //
// $str (string)     - строка выводится после Ника юзера  //
// $text (string)    - выводится после строки со статусом //
// $sub (string)     - строка выводится в области "sub"   //
////////////////////////////////////////////////////////////
    global $set_user, $realtime, $user_id, $admp, $home;
    $out = false;
    if (!$user['id']) {
        $out = '<b>Гость</b>';
        if (!empty ($user['name']))
            $out .= ': ' . $user['name'];
        if (!empty ($str))
            $out .= ' ' . $str;
    }
    else {
        if ($set_user['avatar']) {
            $out .= '<table cellpadding="0" cellspacing="0"><tr><td>';
            if (file_exists(('../files/avatar/' . $user['id'] . '.png')))
                $out .= '<img src="../files/avatar/' . $user['id'] . '.png" width="32" height="32" alt="' . $user['name'] . '" />&nbsp;';
            else
                $out .= '<img src="../images/empty.png" width="32" height="32" alt="' . $user['name'] . '" />&nbsp;';
            $out .= '</td><td>';
        }
        if ($user['sex'])
            $out .= '<img src="../theme/' . $set_user['skin'] . '/images/' . ($user['sex'] == 'm' ? 'm' : 'w') . ($user['datereg'] > $realtime - 86400 ? '_new' : '') . '.png" width="16" height="16" align="middle" />&nbsp;';
        else
            $out .= '<img src="../images/del.png" width="12" height="12" align="middle" />&nbsp;';
        $out .= !$user_id || $user_id == $user['id'] ? '<b>' . $user['name'] . '</b>' : '<a href="../str/anketa.php?id=' . $user['id'] . '"><b>' . $user['name'] . '</b></a>';
        $rights = array(0 => '', 1 => '(GMod)', 2 => '(CMod)', 3 => '(FMod)', 4 => '(DMod)', 5 => '(LMod)', 6 => '(Smd)', 7 => '(Adm)', 9 => '(SV!)');
        $out .= ' ' . $rights[$user['rights']];
        $out .= ($realtime > $user['lastdate'] + 300 ? '<span class="red"> [Off]</span>' : '<span class="green"> [ON]</span>');
        if (!empty ($str))
            $out .= ' ' . $str;
        if ($status && !empty ($user['status']))
            $out .= '<div class="status"><img src="../theme/' . $set_user['skin'] . '/images/label.png" alt="" align="middle" />&nbsp;' . $user['status'] . '</div>';
        if ($set_user['avatar'])
            $out .= '</td></tr></table>';
    }
    if ($text)
        $out .= '<div>' . $text . '</div>';
    if ($sub || $ip) {
        $out .= '<div class="sub">';
        if (!empty ($sub))
            $out .= $sub;
        if ($ip) {
            $out .= '<div class="gray"><u>UserAgent</u>:&nbsp;' . $user['browser'] . '<br />';
            if ($ip == 2)
                $out .= '<u>IP Address</u>:&nbsp;<a href="../' . $admp . '/index.php?act=usr_search_ip&amp;ip=' . $user['ip'] . '">' . long2ip($user['ip']) . '</a></div>';
            else
                $out .= '<u>IP Address</u>:&nbsp;' . long2ip($user['ip']) . '</div>';
        }
        $out .= '</div>';
    }
    return $out;
}

function mobileads($mad_siteId = NULL) {
    ////////////////////////////////////////////////////////////
    // Рекламная сеть mobileads.ru                            //
    ////////////////////////////////////////////////////////////
    $out = '';
    $mad_socketTimeout = 2;    // таймаут соединения с сервером mobileads.ru
    ini_set("default_socket_timeout", $mad_socketTimeout);
    $mad_pageEncoding = "UTF-8";    // устанавливаем кодировку страницы
    $mad_ua = urlencode(@ $_SERVER['HTTP_USER_AGENT']);
    $mad_ip = urlencode(@ $_SERVER['REMOTE_ADDR']);
    $mad_xip = urlencode(@ $_SERVER['HTTP_X_FORWARDED_FOR']);
    $mad_ref = urlencode(@ $_SERVER['SERVER_NAME'] . @ $_SERVER['REQUEST_URI']);
    $mad_lines = "";
    $mad_fp = @ fsockopen("mobileads.ru", 80, $mad_errno, $mad_errstr, $mad_socketTimeout);
    if ($mad_fp) {
        // переменная $mad_lines будет содержать массив, непарные элементы которого будут ссылками, парные - названием
        $mad_lines = @ file("http://mobileads.ru/links?id=$mad_siteId&ip=$mad_ip&xip=$mad_xip&ua=$mad_ua&ref=$mad_ref");
    }
    @ fclose($mad_fp);    // вывод ссылок
    for ($malCount = 0; $malCount < count($mad_lines); $malCount += 2) {
        $linkURL = trim($mad_lines[$malCount]);
        $linkName = iconv("Windows-1251", $mad_pageEncoding, $mad_lines[$malCount + 1]);
        $out .= '<a href="' . $linkURL . '">' . $linkName . '</a><br />';
    }
    $_SESSION['mad_links'] = $out;
    $_SESSION['mad_time'] = $realtime;
    return $out;
}

/*
################################################################################
##                                                                            ##
##  Старые функции, которые постепенно будут удаляться.                       ##
##  НЕ ИСПОЛЬЗУЙТЕ их в своих модулях!!!                                      ##
##                                                                            ##
################################################################################
*/

function provcat($catalog) {
    $cat1 = mysql_query("select * from `download` where type = 'cat' and id = '" . $catalog . "';");
    $cat2 = mysql_num_rows($cat1);
    $adrdir = mysql_fetch_array($cat1);
    if (($cat2 == 0) || (!is_dir("$adrdir[adres]/$adrdir[name]"))) {
        echo 'Ошибка при выборе категории<br/><a href="?">К категориям</a><br/>';
        require_once ('../incfiles/end.php');
        exit;
    }
}

function deletcat($catalog) {
    $dir = opendir($catalog);
    while (($file = readdir($dir))) {
        if (is_file($catalog . "/" . $file)) {
            unlink($catalog . "/" . $file);
        }
        else
            if (is_dir($catalog . "/" . $file) && ($file != ".") && ($file != "..")) {
                deletcat($catalog . "/" . $file);
            }
    }
    closedir($dir);
    rmdir($catalog);
}

function format($name) {
    $f1 = strrpos($name, ".");
    $f2 = substr($name, $f1 + 1, 999);
    $fname = strtolower($f2);
    return $fname;
}

?>