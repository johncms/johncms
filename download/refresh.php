<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');
require_once("../incfiles/head.php");

if ($rights == 4 || $rights >= 6) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    $drt = [];
    $dropen = opendir("$loadroot");

    while (($file1 = readdir($dropen))) {
        if ($file1 != "." && $file1 != ".." && $file1 != "index.php") {
            $ob = $db->query("SELECT * FROM `download` WHERE type = 'cat' AND refid = ''");

            while ($ob1 = $ob->fetch()) {
                $drt[] = $ob1['name'];
            }

            if (!in_array($file1, $drt)) {
                if (is_dir("$loadroot/$file1")) {
                    $db->exec("INSERT INTO `download` VALUES(0,''," . $db->quote($loadroot) . ",'" . time() . "'," . $db->quote($file1) . ",'cat','','',''," . $db->quote($file1) . ",'')");
                }
            }
        }
    }

    $obn = $db->query("SELECT * FROM `download` WHERE type = 'cat' ;");

    while ($obn1 = $obn->fetch()) {
        $dirop = "$obn1[adres]/$obn1[name]";

        if (is_dir("$dirop")) {
            $diropen = opendir("$dirop");

            while (($file = readdir($diropen))) {
                if ($file != "." && $file != ".." && $file != "index.php") {
                    $pap = "$obn1[adres]/$obn1[name]";
                    $obn2 = $db->query("SELECT * FROM `download` WHERE name = " . $db->quote($file) . " AND adres = " . $db->quote($pap) . "");

                    while ($obndir = $obn2->fetch()) {
                        $fod[] = $obndir['name'];
                    }

                    if (!in_array($file, $fod)) {
                        if (is_dir("$dirop/$file")) {
                            $db->exec("INSERT INTO `download` VALUES(0,'" . $obn1['id'] . "'," . $db->quote($pap) . ",'" . time() . "'," . $db->quote($file) . ",'cat','','',''," . $db->quote($file) . ",'')");
                        }

                        if (is_file("$dirop/$file")) {
                            $db->quote("INSERT INTO `download` VALUES(0,'" . $obn1['id'] . "'," . $db->quote($pap) . ",'" . time() . "'," . $db->quote($file) . ",'file','','','','','')");
                        }
                    }

                    $fod = [];
                }
            }
        }
    }

    $totald = $db->query("SELECT * FROM `download` WHERE type = 'cat' AND time = '" . time() . "'")->rowCount();
    $totalf = $db->query("SELECT * FROM `download` WHERE type = 'file' AND time = '" . time() . "'")->rowCount();
    $deld = $db->query("SELECT * FROM `download` WHERE type = 'cat'");
    $idd = 0;

    while ($deld1 = $deld->fetch()) {
        if (!is_dir("$deld1[adres]/$deld1[name]")) {
            $db->exec("DELETE FROM `download` WHERE id='" . $deld1['id'] . "' LIMIT 1");
            $idd = $idd + 1;
        }
    }

    $delf = $db->query("SELECT * FROM `download` WHERE type = 'file'");
    $idf = 0;

    while ($delf1 = $delf->fetch()) {
        if (!is_file("$delf1[adres]/$delf1[name]")) {
            $db->exec("DELETE FROM `download` WHERE id='" . $delf1['id'] . "' LIMIT 1");
            $idf = $idf + 1;
        }
    }

    echo '<h3>' . $lng_dl['refreshed'] . "</h3>" . $lng_dl['added'] . " $totald " . $lng_dl['folders'] . " и $totalf " . $lng_dl['files'] . "<br>
" . $lng_dl['deleted'] . " $idd " . $lng_dl['folders'] . " и $idf " . $lng_dl['files'] . "<br>";

    if ($totald != 0 || $totalf != 0) {
        echo "<a href='?act=refresh'>" . $lng_dl['refresh_continue'] . "</a><br>";
    }
}

echo "<p><a href='?'>" . $lng['back'] . "</a></p>";
