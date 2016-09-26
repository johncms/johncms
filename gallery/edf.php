<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights >= 6) {
    if ($_GET['id'] == "") {
        echo "ERROR<br><a href='index.php'>Back</a><br>";
        require_once('../incfiles/end.php');
        exit;
    }

    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);
    $ms = $db->query("SELECT * FROM `gallery` WHERE id='" . $id . "'")->fetch();

    if ($ms['type'] != "ft") {
        echo "ERROR<br><a href='index.php'>Back</a><br>";
        require_once('../incfiles/end.php');
        exit;
    }

    if (isset($_POST['submit'])) {
        $text = isset($_POST['text']) ? trim($_POST['text']) : '';
        $db->query("UPDATE `gallery` SET text = " . $db->quote($text) . " WHERE id='" . $id . "'");
        header("location: index.php?id=$ms[refid]");
    } else {
        echo $lng_gal['edit_description'] . "<br><form action='index.php?act=edf&amp;id=" . $id . "' method='post'><input type='text' name='text' value='" . $ms['text'] .
            "'/><br><input type='submit' name='submit' value='Ok!'/></form><br><a href='index.php?id=" . $ms['refid'] . "'>" . _t('Back') . "</a><br>";
    }
} else {
    header("location: index.php");
}
