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

if ($rights >= 6) {
    $error = true;
    if ($id) {
        $stmt = $db->query("SELECT * from `gallery` where `id`='" . $id . "' AND `type` = 'rz' LIMIT 1;");
        if ($stmt->rowCount()) {
            $error = false;
            $ms = $stmt->fetch();
        }
    }
    if (!$error) {
        if (isset($_POST['submit'])) {
            $text = isset($_POST['text']) ? functions::checkin($_POST['text']) : '';
            if ($text) {
                $stmt = $db->prepare("INSERT INTO `gallery` SET 
                    `refid` = '" . $id . "',
                    `time`  = '" . time() . "',
                    `type`  = 'al',
                    `avtor` = '',
                    `text`  = ?,
                    `name`  = ''
                ");
                $stmt->execute([
                    $text
                ]);
                header("location: index.php?id=$id"); exit;
            } else {
                echo functions::display_error($lng['error_empty_fields']);
            }
        } else {
            echo $lng_gal['create_album'] . "<br/><form action='index.php?act=cral&amp;id=" . $id .
                "' method='post'>" . $lng['title'] . ":<br/><input type='text' name='text'/><br/><input type='submit' name='submit' value='" . $lng['save'] . "'/></form><br/><a href='index.php?id=" . $id . "'>" . $lng_gal['to_section'] . "</a><br/>";
        }
    } else {
        echo "ERROR<br/><a href='index.php'>Back</a><br/>";
    }
} else {
    header("location: index.php"); exit;
}
