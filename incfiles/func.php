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

/*
-----------------------------------------------------------------
Обработка ссылок и тэгов BBCODE в тексте
-----------------------------------------------------------------
*/
function tags($var = '') {
    $var = preg_replace(array ('#\[php\](.+?)\[\/php\]#se'), array ("''.highlight('$1').''"), str_replace("]\n", "]", $var));
    $var = preg_replace('#\[b\](.+?)\[/b\]#si', '<span style="font-weight: bold;">\1</span>', $var);
    $var = preg_replace('#\[i\](.+?)\[/i\]#si', '<span style="font-style:italic;">\1</span>', $var);
    $var = preg_replace('#\[u\](.+?)\[/u\]#si', '<span style="text-decoration:underline;">\1</span>', $var);
    $var = preg_replace('#\[s\](.+?)\[/s\]#si', '<span style="text-decoration: line-through;">\1</span>', $var);
    $var = preg_replace('#\[red\](.+?)\[/red\]#si', '<span style="color:red">\1</span>', $var);
    $var = preg_replace('#\[green\](.+?)\[/green\]#si', '<span style="color:green">\1</span>', $var);
    $var = preg_replace('#\[blue\](.+?)\[/blue\]#si', '<span style="color:blue">\1</span>', $var);
    $var = preg_replace('#\[c\](.+?)\[/c\]#si', '<div class="quote">\1</div>', $var);
    $var = preg_replace_callback('~\\[url=(https?://.+?)\\](.+?)\\[/url\\]|(https?://[0-9a-z\.-]+\.[a-z0-9]{2,6}((&amp;)?[0-9a-zA-Z/\.\?\~=_%])*)~', 'url_replace', $var);
    return $var;
}

/*
-----------------------------------------------------------------
Служебная функция подсветки PHP кода
-----------------------------------------------------------------
*/
function highlight($php) {
    $php = strtr($php, array (
        '<br />' => '',
        '\\' => 'slash_JOHNCMS'
    ));

    $php = html_entity_decode(trim($php), ENT_QUOTES, 'UTF-8');
    $php = substr($php, 0, 2) != "<?" ? $php = "<?php\n" . $php . "\n?>" : $php;
    $php = highlight_string(stripslashes($php), true);
    $php = strtr($php, array (
        'slash_JOHNCMS' => '&#92;',
        ':' => '&#58;',
        '[' => '&#91;',
        '&nbsp;' => ' '
    ));

    return '<div class="phpcode">' . $php . '</div>';
}

/*
-----------------------------------------------------------------
Служебная функция парсинга URL
-----------------------------------------------------------------
*/
function url_replace($m) {
    if (!isset($m[3]))
        return '<a href="' . str_replace(':', '&#58;', $m[1]) . '">' . str_replace(':', '&#58;', $m[2]) . '</a>';
    else {
        $m[3] = str_replace(':', '&#58;', $m[3]);
        return '<a href="' . $m[3] . '">' . $m[3] . '</a>';
    }
}

/*
-----------------------------------------------------------------
Транслитерация текста
-----------------------------------------------------------------
*/
function trans($str) {
    $str = strtr($str, array (
        'a' => 'а',
        'b' => 'б',
        'v' => 'в',
        'g' => 'г',
        'd' => 'д',
        'e' => 'е',
        'yo' => 'ё',
        'zh' => 'ж',
        'z' => 'з',
        'i' => 'и',
        'j' => 'й',
        'k' => 'к',
        'l' => 'л',
        'm' => 'м',
        'n' => 'н',
        'o' => 'о',
        'p' => 'п',
        'r' => 'р',
        's' => 'с',
        't' => 'т',
        'u' => 'у',
        'f' => 'ф',
        'h' => 'х',
        'c' => 'ц',
        'ch' => 'ч',
        'w' => 'ш',
        'sh' => 'щ',
        'q' => 'ъ',
        'y' => 'ы',
        'x' => 'э',
        'yu' => 'ю',
        'ya' => 'я',
        'A' => 'А',
        'B' => 'Б',
        'V' => 'В',
        'G' => 'Г',
        'D' => 'Д',
        'E' => 'Е',
        'YO' => 'Ё',
        'ZH' => 'Ж',
        'Z' => 'З',
        'I' => 'И',
        'J' => 'Й',
        'K' => 'К',
        'L' => 'Л',
        'M' => 'М',
        'N' => 'Н',
        'O' => 'О',
        'P' => 'П',
        'R' => 'Р',
        'S' => 'С',
        'T' => 'Т',
        'U' => 'У',
        'F' => 'Ф',
        'H' => 'Х',
        'C' => 'Ц',
        'CH' => 'Ч',
        'W' => 'Ш',
        'SH' => 'Щ',
        'Q' => 'Ъ',
        'Y' => 'Ы',
        'X' => 'Э',
        'YU' => 'Ю',
        'YA' => 'Я'
    ));

    return $str;
}

/*
-----------------------------------------------------------------
Функция постраничной навигации
-----------------------------------------------------------------
*/
function pagenav($base_url, $start, $max_value, $num_per_page) {
    $pgcont = 4;
    $pgcont = (int)($pgcont - ($pgcont % 2)) / 2;

    if ($start >= $max_value)
        $start = max(0, (int)$max_value - (((int)$max_value % (int)$num_per_page) == 0 ? $num_per_page : ((int)$max_value % (int)$num_per_page)));
    else
        $start = max(0, (int)$start - ((int)$start % (int)$num_per_page));
    $base_link = '<a class="navpg" href="' . strtr($base_url, array ('%' => '%%')) . 'start=%d' . '">%s</a> ';
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
    $tmpMaxPages = (int)(($max_value - 1) / $num_per_page) * $num_per_page;

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

/*
-----------------------------------------------------------------
Функция пересчета на дни, или часы
-----------------------------------------------------------------
*/
function timecount($var) {
    $str = '';

    if ($var < 0)
        $var = 0;
    $day = ceil($var / 86400);

    if ($var > 345600) {
        $str = $day . ' дней';
    }  elseif ($var >= 172800) {
        $str = $day . ' дня';
    }  elseif ($var >= 86400) {
        $str = '1 день';
    } else {
        $str = gmdate('G:i:s', $var);
    }
    return $str;
}

/*
-----------------------------------------------------------------
Форматирование размера файлов
-----------------------------------------------------------------
*/
function formatsize($size) {
    if ($size >= 1073741824) {
        $size = round($size / 1073741824 * 100) / 100 . ' Gb';
    }  elseif ($size >= 1048576) {
        $size = round($size / 1048576 * 100) / 100 . ' Mb';
    }  elseif ($size >= 1024) {
        $size = round($size / 1024 * 100) / 100 . ' Kb';
    } else {
        $size = $size . ' b';
    }
    return $size;
}

/*
-----------------------------------------------------------------
Проверка переменных
-----------------------------------------------------------------
*/
function check($str) {
    $str = htmlentities(trim($str), ENT_QUOTES, 'UTF-8');
    $str = nl2br($str);
    $str = strtr($str, array (
        chr(0)=> '',
        chr(1)=> '',
        chr(2)=> '',
        chr(3)=> '',
        chr(4)=> '',
        chr(5)=> '',
        chr(6)=> '',
        chr(7)=> '',
        chr(8)=> '',
        chr(9)=> '',
        chr(10)=> '',
        chr(11)=> '',
        chr(12)=> '',
        chr(13)=> '',
        chr(14)=> '',
        chr(15)=> '',
        chr(16)=> '',
        chr(17)=> '',
        chr(18)=> '',
        chr(19)=> '',
        chr(20)=> '',
        chr(21)=> '',
        chr(22)=> '',
        chr(23)=> '',
        chr(24)=> '',
        chr(25)=> '',
        chr(26)=> '',
        chr(27)=> '',
        chr(28)=> '',
        chr(29)=> '',
        chr(30)=> '',
        chr(31)=> ''
    ));

    $str = str_replace("\'", "&#39;", $str);
    $str = str_replace('\\', "&#92;", $str);
    $str = str_replace("|", "I", $str);
    $str = str_replace("||", "I", $str);
    $str = str_replace("/\\\$/", "&#36;", $str);
    $str = mysql_real_escape_string($str);
    return $str;
}

/*
-----------------------------------------------------------------
Обработка текстов перед выводом на экран
-----------------------------------------------------------------
*/
function checkout($str, $br = 0, $tags = 0) {
    $str = htmlentities($str, ENT_QUOTES, 'UTF-8');

    if ($br == 1)
        $str = nl2br($str);
    elseif ($br == 2)
        $str = str_replace("\r\n", ' ', $str);

    if ($tags == 1)
        $str = tags($str);
    elseif ($tags == 2)
        $str = notags($str);
    $str = strtr($str, array (
        chr(0)=> '',
        chr(1)=> '',
        chr(2)=> '',
        chr(3)=> '',
        chr(4)=> '',
        chr(5)=> '',
        chr(6)=> '',
        chr(7)=> '',
        chr(8)=> '',
        chr(9)=> '',
        chr(10)=> '',
        chr(11)=> '',
        chr(12)=> '',
        chr(13)=> '',
        chr(14)=> '',
        chr(15)=> '',
        chr(16)=> '',
        chr(17)=> '',
        chr(18)=> '',
        chr(19)=> '',
        chr(20)=> '',
        chr(21)=> '',
        chr(22)=> '',
        chr(23)=> '',
        chr(24)=> '',
        chr(25)=> '',
        chr(26)=> '',
        chr(27)=> '',
        chr(28)=> '',
        chr(29)=> '',
        chr(30)=> '',
        chr(31)=> ''
    ));

    return $str;
}

/*
-----------------------------------------------------------------
Обработка смайлов
-----------------------------------------------------------------
*/
function smileys($str, $adm = 0) {
    global $rootpath;

    if (file_exists($rootpath . 'cache/smileys_cache.dat')) {
        $file = file($rootpath . 'cache/smileys_cache.dat');
        $smileys = unserialize($file[0]);
        if ($adm)
            $smileys = array_merge($smileys, unserialize($file[1]));
        return strtr($str, $smileys);
    } else {
        return $str;
    }
}

/*
-----------------------------------------------------------------
Сообщения об ошибках
-----------------------------------------------------------------
*/
function display_error($error = false, $link = '') {
    if ($error) {
        $out = '<div class="rmenu"><p><b>ОШИБКА!</b>';
        if (is_array($error)) {
            foreach ($error as $val)$out .= '<div>' . $val . '</div>';
        } else {
            $out .= '<br />' . $error;
        }
        $out .= '</p><p>' . $link . '</p></div>';
        return $out;
    } else {
        return false;
    }
}

/*
-----------------------------------------------------------------
Рекламная сеть mobileads.ru
-----------------------------------------------------------------
*/
function mobileads($mad_siteId = NULL) {
    $out = '';
    $mad_socketTimeout = 2;      // таймаут соединения с сервером mobileads.ru
    ini_set("default_socket_timeout", $mad_socketTimeout);
    $mad_pageEncoding = "UTF-8"; // устанавливаем кодировку страницы
    $mad_ua = urlencode(@$_SERVER['HTTP_USER_AGENT']);
    $mad_ip = urlencode(@$_SERVER['REMOTE_ADDR']);
    $mad_xip = urlencode(@$_SERVER['HTTP_X_FORWARDED_FOR']);
    $mad_ref = urlencode(@$_SERVER['SERVER_NAME'] . @$_SERVER['REQUEST_URI']);
    $mad_lines = "";
    $mad_fp = @fsockopen("mobileads.ru", 80, $mad_errno, $mad_errstr, $mad_socketTimeout);

    if ($mad_fp) {
        // переменная $mad_lines будет содержать массив, непарные элементы которого будут ссылками, парные - названием
        $mad_lines = @file("http://mobileads.ru/links?id=$mad_siteId&ip=$mad_ip&xip=$mad_xip&ua=$mad_ua&ref=$mad_ref");
    }
    @fclose($mad_fp); // вывод ссылок

    for ($malCount = 0; $malCount < count($mad_lines); $malCount += 2) {
        $linkURL = trim($mad_lines[$malCount]);
        $linkName = iconv("Windows-1251", $mad_pageEncoding, $mad_lines[$malCount + 1]);
        $out .= '<a href="' . $linkURL . '">' . $linkName . '</a><br />';
    }
    $_SESSION['mad_links'] = $out;
    $_SESSION['mad_time'] = $realtime;
    return $out;
}

function provcat($catalog) {
    $cat1 = mysql_query("select * from `download` where type = 'cat' and id = '" . $catalog . "';");
    $cat2 = mysql_num_rows($cat1);
    $adrdir = mysql_fetch_array($cat1);
    if (($cat2 == 0) || (!is_dir("$adrdir[adres]/$adrdir[name]"))) {
        echo 'ERROR<br/><a href="?">Back</a><br/>';
        require_once('../incfiles/end.php');
        exit;
    }
}
?>
