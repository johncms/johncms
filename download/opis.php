<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

require_once ("../incfiles/head.php");

if ($rights == 4 || $rights >= 6) {
    if ($_GET['file'] == "") {
        echo $lng_dl['file_not_selected'] . "<br/><a href='?'>" . $lng['back'] . "</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }

    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    $file = intval($_GET['file']);
    $file1 = $db->query("SELECT * FROM `download` WHERE `type` = 'file' AND `id` = '" . $file . "';");
    $file2 = $file1->rowCount();
    $adrfile = $file1->fetch();

    if (($file1 == 0) || (!is_file("$adrfile[adres]/$adrfile[name]"))) {
        echo $lng_dl['file_not_selected'] . "<br/><a href='?'>" . $lng['back'] . "</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }

    $stt = "$adrfile[text]";

    if (isset ($_POST['submit'])) {
        $newt = trim($_POST['newt']);
        $db->exec("update `download` set `text`=" . $db->quote($newt) . " where `id`='" . $file . "'");
        echo $lng_dl['description_changed'] . "<br/>";
    }

    else {
        $str = str_replace("<br/>", "\r\n", $adrfile['text']);
        echo "<form action='?act=opis&amp;file=" . $file . "' method='post'>";
        echo $lng['description'] . ':<br/><textarea rows="4" name="newt">' . $str . '</textarea><br/>';
        echo "<input type='submit' name='submit' value='Изменить'/></form><br/>";
    }
}
else {
    echo "Нет доступа!";
}

echo "<p><a href='?act=view&amp;file=" . $file . "'>" . $lng['back'] . "</a></p>";
