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

function antilink($str)
{
    $str = strtr($str, array(".ru" => "***", ".com" => "***", ".net" => "***", ".org" => "***", ".info" => "***", ".mobi" => "***", ".wen" => "***", ".kmx" => "***", ".h2m" => "***"));
    return $str;
}

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

function trans($str)
{
    $str = strtr($str, array("a" => "а", "b" => "б", "v" => "в", "g" => "г", "d" => "д", "e" => "е", "yo" => "ё", "zh" => "ж", "z" => "з", "i" => "и", "j" => "й", "k" => "к", "l" => "л", "m" => "м", "n" => "н", "o" => "о", "p" => "п", "r" =>
        "р", "s" => "с", "t" => "т", "u" => "у", "f" => "ф", "h" => "х", "c" => "ц", "ch" => "ч", "w" => "ш", "sh" => "щ", "q" => "ъ", "y" => "ы", "x" => "э", "yu" => "ю", "ya" => "я", "A" => "А", "B" => "Б", "V" => "В", "G" => "Г", "D" => "Д", "E" =>
        "Е", "YO" => "Ё", "ZH" => "Ж", "Z" => "З", "I" => "И", "J" => "Й", "K" => "К", "L" => "Л", "M" => "М", "N" => "Н", "O" => "О", "P" => "П", "R" => "Р", "S" => "С", "T" => "Т", "U" => "У", "F" => "Ф", "H" => "Х", "C" => "Ц", "CH" => "Ч", "W" =>
        "Ш", "SH" => "Щ", "Q" => "Ъ", "Y" => "Ы", "X" => "Э", "YU" => "Ю", "YA" => "Я"));
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

function offimg($str)
{
    return eregi_replace("((<img src|alt)[-a-zA-Z0-9@:%_\+.~#?;&//=\(\)/\'\"\ />]+)", "", $str);

}
#################################3
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
############################
function tegi($str)
{
    $str = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class=\'d\'>\1<br/></div>', $str);
    $str = eregi_replace("\\[l\\]([[:alnum:]_=:/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/;%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='http://\\1'>\\6</a>", $str);

    if (stristr($str, "<a href="))
    {
        $str = eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)</a>",
            "<a href='\\1\\3'>\\3</a>", $str);
    } else
    {
        $str = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "<a href='\\1\\3'>\\3</a>", $str);
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