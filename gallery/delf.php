<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights >= 6) {
    if ($_GET['id'] == "") {
        echo "ERROR<br><a href='index.php'>Back</a><br>";
        require_once('../incfiles/end.php');
        exit;
    }

    $id = intval($_GET['id']);

    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);
    $ms = $db->query("SELECT * FROM `gallery` WHERE id= " . $id)->fetch();

    if ($ms['type'] != "ft") {
        echo "ERROR<br><a href='index.php'>Back</a><br>";
        require_once('../incfiles/end.php');
        exit;
    }

    if (isset($_GET['yes'])) {
        $km = $db->query("SELECT * FROM `gallery` WHERE type='km' AND refid='" . $id . "'");

        while ($km1 = $km->fetch()) {
            $db->exec("DELETE FROM `gallery` WHERE `id`='" . $km1['id'] . "'");
        }

        unlink("foto/$ms[name]");
        $db->exec("DELETE FROM `gallery` WHERE `id`='" . $id . "'");
        header("location: index.php?id=$ms[refid]");
    } else {
        echo $lng['delete_confirmation'] . "<br>";
        echo "<a href='index.php?act=delf&amp;id=" . $id . "&amp;yes'>" . $lng['delete'] . "</a> | <a href='index.php?id=" . $ms['refid'] . "'>" . $lng['cancel'] . "</a><br>";
    }
}
