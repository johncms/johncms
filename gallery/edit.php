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

    switch ($ms['type']) {
        case "al":
            if (isset($_POST['submit'])) {
                $text = functions::check($_POST['text']);
                $db->exec("UPDATE `gallery` SET text='" . $text . "' WHERE id='" . $id . "';");
                header("location: index.php?id=$id");
            } else {
                echo $lng_gal['edit_album'] . "<br><form action='index.php?act=edit&amp;id=" . $id . "' method='post'><input type='text' name='text' value='" . $ms['text'] .
                    "'/><br><input type='submit' name='submit' value='Ok!'/></form><br><a href='index.php?id=" . $id . "'>" . $lng['back'] . "</a><br>";
            }
            break;

        case "rz":
            if (isset($_POST['submit'])) {
                $text = functions::check($_POST['text']);

                if (!empty($_POST['user'])) {
                    $user = intval($_POST['user']);
                } else {
                    $user = 0;
                }

                $db->exec("UPDATE `gallery` SET text='" . $text . "', user='" . $user . "' WHERE id='" . $id . "';");
                header("location: index.php?id=$id");
            } else {
                echo $lng_gal['edit_section'] . "<br><form action='index.php?act=edit&amp;id=" . $id . "' method='post'><input type='text' name='text' value='" . $ms['text'] . "'/><br>";
                echo "<input type='submit' name='submit' value='Ok!'/></form><br><a href='index.php?id=" . $id . "'>" . $lng['back'] . "</a><br>";
            }
            break;

        default:
            echo "ERROR<br><a href='index.php'>Back</a><br>";
            require_once('../incfiles/end.php');
            exit;
            break;
    }
} else {
    header("location: index.php");
}
