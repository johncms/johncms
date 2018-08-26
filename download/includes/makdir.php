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

require_once("../incfiles/head.php");
if ($rights == 4 || $rights >= 6) {
    $error = false;
    if (!$cat) {
        $droot = $loadroot;
    } else {
        $stmt = $db->query('SELECT * FROM `download` WHERE `type` = "cat" AND `id` = "' . $cat. '" LIMIT 1');
        if ($stmt->rowCount()) {
            $res = $stmt->fetch();
            if (!is_dir($res['adres'] . '/' . $res['name'])) {
                $error = true;
            } else {
                $droot = $res['adres'] . '/' . $res['name'];
            }
        } else {
            $error = true;
        }
    }
    if (!$error) {
        if (isset ($_POST['submit'])) {
            $drn = functions::checkin($_POST['drn']);
            $rusn = functions::checkin($_POST['rusn']);
            if (!preg_match('/[^a-z0-9]/', $drn)) {
                $mk = mkdir($droot . '/' . $drn, 0777);
                if ($mk == true) {
                    chmod("$droot/$drn", 0777);
                    echo "Папка создана<br/>";
                    $stmt = $db->prepare("INSERT INTO `download` SET
                        `refid` = $cat,
                        `adres` = '" . $droot . "',
                        `time` = " . time() . ",
                        `name` = '$drn',
                        `type` = 'cat',
                        `ip` = '',
                        `soft` = '',
                        `text` = ?,
                        `screen` = ''
                    ");
                    $stmt->execute([
                        $rusn
                    ]);
                    $newcat = $db->lastInsertId();
                    echo "&#187;<a href='?cat=" . $newcat . "'>" . $lng['back'] . "</a><br/>";
                } else {
                    echo "ERROR<br/>";
                }
            } else {
                echo "ERROR<br/>";
            }
        } else {
            echo "<form action='?act=makdir&amp;cat=" . $cat . "' method='post'>
             <p>" . $lng_dl['folder_name'] . "<br />
             <input type='text' name='drn'/></p>
             <p>" . $lng_dl['folder_name_for_list'] . ":<br/>
             <input type='text' name='rusn'/></p>
             <p><input type='submit' name='submit' value='Создать'/></p>
             </form>";
        }
    } else {
        echo 'ERROR<br/><a href="?">Back</a><br/>';
    }
}
echo "<a href='?'>" . $lng['back'] . "</a><br/>";