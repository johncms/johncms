<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights >= 6) {
    if (empty($_GET['id'])) {
        echo "ERROR<br><a href='index.php'>Back</a><br>";
        require_once('../incfiles/end.php');
        exit;
    }

    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);
    $ms = $db->query("SELECT * FROM `gallery` WHERE id = " . $id)->fetch();

    if ($ms['type'] != "rz") {
        echo "ERROR<br><a href='index.php'>Back</a><br>";
        require_once('../incfiles/end.php');
        exit;
    }

    if (isset($_POST['submit'])) {
        $text = isset($_POST['text']) ? trim($_POST['text']) : '';
        $db->exec("INSERT INTO `gallery` VALUES(0,'" . $id . "','" . time() . "','al',''," . $db->quote($text) . ",'','','','');");
        header("location: index.php?id=$id");
    } else {
        echo $lng_gal['create_album'] . "<br><form action='index.php?act=cral&amp;id=" . $id .
            "' method='post'>" . $lng['title'] . ":<br><input type='text' name='text'/><br><input type='submit' name='submit' value='" . $lng['save'] . "'/></form><br><a href='index.php?id=" . $id . "'>" . $lng_gal['to_section'] . "</a><br>";
    }
} else {
    header("location: index.php");
}
