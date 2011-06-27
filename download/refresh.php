<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');
require_once("../incfiles/head.php");
if ($rights == 4 || $rights >= 6) {
    $drt = array ();
    $dropen = opendir("$loadroot");
    while (($file1 = readdir($dropen))) {
        if ($file1 != "." && $file1 != ".." && $file1 != "index.php") {
            $ob = mysql_query("select * from `download` where type = 'cat' and refid = ''  ;");
            while ($ob1 = mysql_fetch_array($ob)) {
                $drt[] = $ob1[name];
            }
            if (!in_array($file1, $drt)) {
                if (is_dir("$loadroot/$file1")) {
                    mysql_query("insert into `download` values(0,'','" . $loadroot . "','" . time() . "','" . $file1 . "','cat','','','','" . $file1 . "','');");
                }
            }
        }
    }
    $obn = mysql_query("select * from `download` where type = 'cat' ;");
    while ($obn1 = mysql_fetch_array($obn)) {
        $dirop = "$obn1[adres]/$obn1[name]";
        if (is_dir("$dirop")) {
            $diropen = opendir("$dirop");
            while (($file = readdir($diropen))) {
                if ($file != "." && $file != ".." && $file != "index.php") {
                    $pap = "$obn1[adres]/$obn1[name]";
                    $obn2 = mysql_query("select * from `download` where name = '" . functions::check($file) . "' and adres = '" . $pap . "' ;");
                    while ($obndir = mysql_fetch_array($obn2)) {
                        $fod[] = $obndir[name];
                    }
                    if (!in_array($file, $fod)) {
                        if (is_dir("$dirop/$file")) {
                            mysql_query("insert into `download` values(0,'" . $obn1[id] . "','" . $pap . "','" . time() . "','" . $file . "','cat','','','','" . $file . "','');");
                        }
                        if (is_file("$dirop/$file")) {
                            mysql_query("insert into `download` values(0,'" . $obn1[id] . "','" . $pap . "','" . time() . "','" . $file . "','file','','','','','');");
                        }
                    }
                    $fod = array (); ########## 7.02.08
                }
            }
        }
    }
    $dres = mysql_query("select * from `download` where type = 'cat' and time = '" . time() . "' ;");
    $totald = mysql_num_rows($dres);
    $fres = mysql_query("select * from `download` where type = 'file' and time = '" . time() . "' ;");
    $totalf = mysql_num_rows($fres);
    $deld = mysql_query("select * from `download` where type = 'cat' ;");
    $idd = 0;
    while ($deld1 = mysql_fetch_array($deld)) {
        if (!is_dir("$deld1[adres]/$deld1[name]")) {
            mysql_query("delete from `download` where id='" . $deld1[id] . "' LIMIT 1;");
            $idd = $idd + 1;
        }
    }
    $delf = mysql_query("select * from `download` where type = 'file' ;");
    $idf = 0;
    while ($delf1 = mysql_fetch_array($delf)) {
        if (!is_file("$delf1[adres]/$delf1[name]")) {
            mysql_query("delete from `download` where id='" . $delf1[id] . "' LIMIT 1;");
            $idf = $idf + 1;
        }
    }
    echo '<h3>' . $lng_dl['refreshed'] . "</h3>" . $lng_dl['added'] . " $totald " . $lng_dl['folders'] . " и $totalf " . $lng_dl['files'] . "<br/>
" . $lng_dl['deleted'] . " $idd " . $lng_dl['folders'] . " и $idf " . $lng_dl['files'] . "<br/>";
    if ($totald != 0 || $totalf != 0) {
        echo "<a href='?act=refresh'>" . $lng_dl['refresh_continue'] . "</a><br/>";
    }
}
echo "<p><a href='?'>" . $lng['back'] . "</a></p>";
?>