<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');
require_once("../incfiles/head.php");

if ($rights == 4 || $rights >= 6) {
    if ($_GET['file'] == "") {
        echo $lng_dl['file_not_selected'] . "<br><a href='?'>" . $lng['back'] . "</a><br>";
        require_once('../incfiles/end.php');
        exit;
    }

    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    $file = intval(trim($_GET['file']));
    $file1 = $db->query("SELECT * FROM `download` WHERE type = 'file' AND id = '" . $file . "';");
    $file2 = $file1->rowCount();
    $adrfile = $file1->fetch();

    if (!$file1 || !is_file($adrfile['adres'] . '/' . $adrfile['name'])) {
        echo $lng_dl['file_not_selected'] . "<br><a href='?'>" . $lng['back'] . "</a><br>";
        require_once('../incfiles/end.php');
        exit;
    }

    $refd1 = $db->query("SELECT * FROM `download` WHERE type = 'cat' AND `id` = '" . $adrfile['refid'] . "'")->fetch();

    if (isset($_POST['submit'])) {
        unlink($adrfile['adres'] . '/' . $adrfile['name']);
        $db->exec("DELETE FROM `download` WHERE `id` = " . $adrfile['id']);
        echo '<p>' . $lng_dl['file_deleted'] . '</p>';
    } else {
        echo '<p>' . $lng['delete_confirmation'] . '</p>' .
            '<form action="index.php?act=dfile&amp;file=' . $file . '" method="post">' .
            '<input type="submit" name="submit" value="' . $lng['delete'] . '" />' .
            '</form><p><a href="index.php?act=view&amp;file=' . $file . '">' . $lng['cancel'] . '</a></p>';
    }
}

echo "<p><a href='?cat=" . $refd1['id'] . "'>" . $lng['back'] . "</a></p>";
