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

defined('_IN_PUSTO') or die('Error:restricted access');

#######################
function provcat($catalog)
{
    $cat1 = mysql_query("select * from `download` where type = 'cat' and id = '" . $catalog . "';");
    $cat2 = mysql_num_rows($cat1);
    $adrdir = mysql_fetch_array($cat1);
    if (($cat2 == 0) || (!is_dir("$adrdir[adres]/$adrdir[name]")))
    {
        echo "Ошибка при выборе категории<br/><a href='?'>К категориям</a><br/>";
        require ('../incfiles/end.php');
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
        require ('../incfiles/end.php');
        exit;
    }
}
#########################
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
####################
function format($name)
{
    $f1 = strrpos($name, ".");
    $f2 = substr($name, $f1 + 1, 999);
    $fname = strtolower($f2);
    return $fname;
}


##################################
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
############################
function trans($str)
{
    $str = strtr($str, array("a" => "а", "b" => "б", "v" => "в", "g" => "г", "d" => "д", "e" => "е", "yo" => "ё", "zh" => "ж", "z" => "з", "i" => "и", "j" => "й", "k" => "к", "l" => "л", "m" => "м", "n" => "н", "o" => "о", "p" => "п", "r" =>
        "р", "s" => "с", "t" => "т", "u" => "у", "f" => "ф", "h" => "х", "c" => "ц", "ch" => "ч", "w" => "ш", "sh" => "щ", "q" => "ъ", "y" => "ы", "x" => "э", "yu" => "ю", "ya" => "я", "A" => "А", "B" => "Б", "V" => "В", "G" => "Г", "D" => "Д", "E" =>
        "Е", "YO" => "Ё", "ZH" => "Ж", "Z" => "З", "I" => "И", "J" => "Й", "K" => "К", "L" => "Л", "M" => "М", "N" => "Н", "O" => "О", "P" => "П", "R" => "Р", "S" => "С", "T" => "Т", "U" => "У", "F" => "Ф", "H" => "Х", "C" => "Ц", "CH" => "Ч", "W" =>
        "Ш", "SH" => "Щ", "Q" => "Ъ", "Y" => "Ы", "X" => "Э", "YU" => "Ю", "YA" => "Я"));
    return $str;
}
#############################

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
#############################
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
#############################
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

###################################
function offimg($str)
{
    return eregi_replace("((<img src|alt)[-a-zA-Z0-9@:%_\+.~#?;&//=\(\)/\'\"\ />]+)", "", $str);

}

// Задаем кодировку mb_string
mb_internal_encoding('UTF-8');


?>