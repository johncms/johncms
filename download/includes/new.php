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

require_once ("../incfiles/head.php");
echo '<div class="phdr">' . $lng['new_files'] . '</div>';

$total = $db->query("SELECT COUNT(*) FROM `download` WHERE `time` > '" . (time() - 259200) . "' AND `type` = 'file'")->fetchColumn();

if ($total) {
    ////////////////////////////////////////////////////////////
    // Выводим список новых файлов                            //
    ////////////////////////////////////////////////////////////
    $stmt = $db->query("SELECT * FROM `download` WHERE `time` > '" . (time() - 259200) . "' AND `type` = 'file' ORDER BY `time` DESC LIMIT $start, $kmess");
    while ($newf = $stmt->fetch()) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        $fsz = filesize("$newf[adres]/$newf[name]");
        $fsz = round($fsz / 1024, 2);
        $ft = functions::format("$newf[adres]/$newf[name]");
        switch ($ft) {
            case "mp3" :
                $imt = "mp3.png";
                break;
            case "zip" :
                $imt = "rar.png";
                break;
            case "jar" :
                $imt = "jar.png";
                break;
            case "gif" :
                $imt = "gif.png";
                break;
            case "jpg" :
                $imt = "jpg.png";
                break;
            case "png" :
                $imt = "png.png";
                break;
            default :
                $imt = "file.gif";
                break;
        }
        if ($newf['text'] != "") {
            $tx = $newf['text'];
            if (mb_strlen($tx) > 100) {
                $tx = mb_substr(strip_tags($tx), 0, 90);

                $tx = "<br/>$tx...";
            }
            else {
                $tx = "<br/>$tx";
            }
        }
        else {
            $tx = "";
        }
        echo '<img src="' . $filesroot . '/img/' . $imt . '" alt=""/><a href="?act=view&amp;file=' . $newf['id'] . '">' . htmlentities($newf['name'], ENT_QUOTES, 'UTF-8') . '</a> (' . $fsz . ' кб)' . $tx . '<br/>';
        $nadir = $newf['refid'];
        $pat = "";
        while ($nadir != "") {
            $dnew1 = $db->query("select * from `download` where type = 'cat' and id = '" . $nadir . "'")->fetch();
            $pat = "$dnew1[text]/$pat";
            $nadir = $dnew1['refid'];
        }
        $l = mb_strlen($pat);
        $pat1 = mb_substr($pat, 0, $l - 1);
        echo "[$pat1]</div>";
        ++$i;
    }
    echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
    if ($total > $kmess) {
        echo '<p>' . functions::display_pagination('index.php?act=new&amp;', $start, $total, $kmess) . '</p>';
        echo '<p><form action="index.php" method="get"><input type="hidden" value="new" name="act" /><input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
    }
}
else {
    echo '<p>' . $lng['list_empty'] . '</p>';
}
echo "<p><a href='index.php?'>" . $lng['back'] . "</a></p>";
