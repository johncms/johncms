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
    $drt = array();
    $stmt = $db->query("select * from `download` where type = 'cat' and refid = '0'  ;");
    while ($ob1 = $stmt->fetch()) {
        $drt[] = $ob1['name'];
    }
    $dropen = opendir($loadroot);
    $time = time();
    while (($file1 = readdir($dropen))) {
        if ($file1 != "." && $file1 != ".." && $file1 != "index.php") {
            if (!in_array($file1, $drt)) {
                if (is_dir("$loadroot/$file1")) {
                    $db->exec("INSERT INTO `download` SET
                        `refid` = '',
                        `adres` = '" . $loadroot . "',
                        `time` = '" . $time . "',
                        `name` = '" . $file1 . "',
                        `type` = 'cat',
                        `avtor` = '',
                        `ip` = '',
                        `soft` = '',
                        `text` = '" . $file1 . "',
                        `screen` = ''
                    ");
                }
            }
        }
    }
    $stmt = $db->query("select * from `download` where type = 'cat' ;");
    $stmt_2 = $db->prepare('SELECT * FROM `download` WHERE `name` = ? AND `adres` = ? ;');
    $stmt_3 = $db->prepare('INSERT INTO `download` SET
        `refid` = ?,
        `adres` = ?,
        `time` = "' . $time . '",
        `name` = ?,
        `type` = ?,
        `avtor` = "",
        `ip` = "",
        `soft` = "",
        `text` = ?,
        `screen` = ""
    ');
    while ($obn1 = $stmt->fetch()) {
        $dirop = "$obn1[adres]/$obn1[name]";
        if (is_dir("$dirop")) {
            $diropen = opendir("$dirop");
            while (($file = readdir($diropen))) {
                if ($file != "." && $file != ".." && $file != "index.php") {
                    $pap = "$obn1[adres]/$obn1[name]";
                    $stmt_2->execute([
                        $file,
                        $pap
                    ]);
                    while ($obndir = $stmt_2->fetch()) {
                        $fod[] = $obndir['name'];
                    }
                    if (!in_array($file, $fod)) {
                        $type = $text = false;
                        if (is_dir("$dirop/$file")) {
                            $type = 'cat';
                            $text = $file;
                        } elseif (is_file("$dirop/$file")) {
                            $type = 'file';
                            $text = '';
                        }
                        $stmt_2->execute([
                            $obn1['id'],
                            $pap,
                            $file,
                            $type,
                            $text
                        ]);
                    }
                    $fod = array (); ########## 7.02.08
                }
            }
        }
    }
    $stmt_2 = $stmt_3 = null;
    $totald = $db->query("select COUNT(*) from `download` where type = 'cat' and time = '" . $time . "' ;")->fetchColumn();
    $totalf = $db->query("select COUNT(*) from `download` where type = 'file' and time = '" . $time . "' ;")->fetchColumn();
    $stmt = $db->query("select * from `download` where type = 'cat' ;");
    $idd = 0;
    while ($deld1 = $stmt->fetch()) {
        if (!is_dir("$deld1[adres]/$deld1[name]")) {
            $db->exec("delete from `download` where id='" . $deld1[id] . "' LIMIT 1;");
            $idd = $idd + 1;
        }
    }
    $stmt = $db->query("select * from `download` where type = 'file' ;");
    $idf = 0;
    while ($delf1 = $stmt->fetch()) {
        if (!is_file("$delf1[adres]/$delf1[name]")) {
            $db->exec("delete from `download` where id='" . $delf1[id] . "' LIMIT 1;");
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
