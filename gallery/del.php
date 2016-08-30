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
    $ms = $db->query("SELECT * FROM `gallery` WHERE id = " . $id)->fetch();

    if (isset($_GET['yes'])) {
        switch ($ms['type']) {
            case "al":
                $ft = $db->query("SELECT * FROM `gallery` WHERE `type`='ft' AND `refid`='" . $id . "'");

                while ($ft1 = $ft->fetch()) {
                    $km = $db->query("SELECT * FROM `gallery` WHERE type='km' AND refid='" . $ft1['id'] . "'");

                    while ($km1 = $km->fetch()) {
                        $db->exec("DELETE FROM `gallery` WHERE `id`='" . $km1['id'] . "'");
                    }

                    unlink("foto/$ft1[name]");
                    $db->query("DELETE FROM `gallery` WHERE `id`='" . $ft1['id'] . "'");
                }

                $db->query("DELETE FROM `gallery` WHERE `id`='" . $id . "'");
                header("location: index.php?id=$ms[refid]");
                break;

            case "rz":
                $al = $db->query("SELECT * FROM `gallery` WHERE type='al' AND refid='" . $id . "'");

                while ($al1 = $al->fetch()) {
                    $ft = $db->query("SELECT * FROM `gallery` WHERE type='ft' AND refid='" . $al1['id'] . "'");

                    while ($ft1 = $ft->fetch()) {
                        $km = $db->query("SELECT * FROM `gallery` WHERE type='km' AND refid='" . $ft1['id'] . "'");

                        while ($km1 = $km->fetch()) {
                            $db->exec("DELETE FROM `gallery` WHERE `id`='" . $km1['id'] . "'");
                        }
                        unlink("foto/$ft1[name]");
                        $db->exec("DELETE FROM `gallery` WHERE `id`='" . $ft1['id'] . "'");
                    }

                    $db->exec("DELETE FROM `gallery` WHERE `id`='" . $al1['id'] . "'");
                }

                $db->exec("DELETE FROM `gallery` WHERE `id`='" . $id . "'");
                header("location: index.php");
                break;

            default:
                echo "ERROR<br><a href='index.php'>Back</a><br>";
                require_once('../incfiles/end.php');
                exit;
                break;
        }
    } else {
        switch ($ms['type']) {
            case "al":
                echo $lng['delete_confirmation'] . " $ms[text]?<br>";
                break;

            case "rz":
                echo $lng['delete_confirmation'] . " $ms[text]?<br>";
                break;

            default:
                echo "ERROR<br><a href='index.php'>" . $lng['to_gallery'] . "</a><br>";
                require_once('../incfiles/end.php');
                exit;
                break;
        }
        echo "<a href='index.php?act=del&amp;id=" . $id . "&amp;yes'>" . $lng['delete'] . "</a> | <a href='index.php?id=" . $id . "'>" . $lng['cancel'] . "</a><br>";
    }
} else {
    header("location: index.php");
}
