<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

require_once("../incfiles/head.php");

if ($rights == 4 || $rights >= 6) {
    if (empty($_GET['cat'])) {
        echo "ERROR<br /><a href='?'>Back</a><br>";
        require_once('../incfiles/end.php');
        exit;
    }

    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    $cat = intval($_GET['cat']);
    provcat($cat);
    $adrdir = $db->query("SELECT * FROM `download` WHERE type = 'cat' AND id = '" . $cat . "'")->fetch();
    $namedir = "$adrdir[adres]/$adrdir[name]";

    if (isset($_POST['submit'])) {
        if (!empty($_POST['newrus'])) {
            $newrus = trim($_POST['newrus']);
        } else {
            $newrus = "$adrdir[text]";
        }

        if ($db->exec("UPDATE `download` SET text=" . $db->quote($newrus) . " WHERE id='" . $cat . "'")) {
            echo '<p>' . $lng_dl['name_changed'] . '</p>';
        }
    } else {
        echo "<form action='?act=ren&amp;cat=" . $cat . "' method='post'><p>";
        echo $lng_dl['folder_name_for_list'] . "<br><input type='text' name='newrus' value='" . $adrdir['text'] . "'/></p>";
        echo "<p><input type='submit' name='submit' value='" . $lng_dl['change'] . "'/></p></form>";
    }
}

echo "<p><a href='?cat=" . $cat . "'>" . $lng['back'] . "</a></p>";
