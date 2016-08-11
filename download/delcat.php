<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');
require_once("../incfiles/head.php");

function deletcat($catalog)
{
    $dir = opendir($catalog);

    while (($file = readdir($dir))) {
        if (is_file($catalog . "/" . $file)) {
            unlink($catalog . "/" . $file);
        } else {
            if (is_dir($catalog . "/" . $file) && ($file != ".") && ($file != "..")) {
                deletcat($catalog . "/" . $file);
            }
        }
    }

    closedir($dir);
    rmdir($catalog);
}

if (($rights == 4 || $rights >= 6) && (!empty($_GET['cat']))) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    $cat = intval($_GET['cat']);
    $delcat = $db->query("select * from `download` where type = 'cat' and refid = '$cat'");

    if (!$delcat->rowCount()) {
        if (isset($_POST['submit'])) {
            provcat($cat);
            $adrdir = $db->query("select * from `download` where `type` = 'cat' and `id` = '$cat'")->fetch();
            deletcat("$adrdir[adres]/$adrdir[name]");
            $db->exec("DELETE FROM `download` WHERE `id` = '$cat'");
            echo '<p>' . $lng_dl['folder_deleted'] . '<br /><a href="index.php">' . $lng['continue'] . '</a></p>';
        } else {
            echo '<p>' . $lng['delete_confirmation'] . '</p>' .
                '<form action="index.php?act=delcat&amp;cat=' . $cat . '" method="post">' .
                '<input type="submit" name="submit" value="' . $lng['delete'] . '" />' .
                '</form><p><a href="index.php?cat=' . $cat . '">' . $lng['cancel'] . '</a></p>';
        }
    }
}
