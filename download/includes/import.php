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

require_once ("../incfiles/head.php");
if ($rights == 4 || $rights >= 6) {
    $error = false;
    if (!$cat) {
        $loaddir = $loadroot;
    } else {
        $stmt = $db->query('SELECT * FROM `download` WHERE `type` = "cat" AND `id` = "' . $cat. '" LIMIT 1');
        if ($stmt->rowCount()) {
            $res = $stmt->fetch();
            if (!is_dir($res['adres'] . '/' . $res['name'])) {
                $error = true;
            } else {
                $loaddir = $res['adres'] . '/' . $res['name'];
            }
        } else {
            $error = true;
        }
    }
    if (!$error) {
        if (isset($_POST['submit'])) {
            $url = trim($_POST['url']);
            $opis = isset($_POST['opis']) ? functions::checkin($_POST['opis']) : '';
            $newn = isset($_POST['newn']) ? functions::checkin($_POST['newn']) : '';
            $ext = functions::format($url);
            if (!$newn || !$ext || preg_match('/[^a-z0-9.()+_-]/', $newn) || preg_match('/[^a-z0-9]/i', $ext)) {
                echo "В новом названии файла <b>$newn</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='?act=import&amp;cat=" . $cat . "'>" . $lng['back'] . "</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            $import = $loaddir . '/' . $newn . '.' . $ext;
            $files = file($import);
            if (!$files) {
                if (copy($url, $import)) {
                    $ch = $newn . '.' . $ext;
                    echo "Файл успешно загружен<br/>";
                    $stmt = $db->prepare("INSERT INTO `download` SET
                        `refid` = '$cat',
                        `adres` = '" . $loaddir . "',
                        `time` = '" . time() . "',
                        `name` = '" . $ch . "',
                        `type` = 'file',
                        `avtor` = '',
                        `ip` = '',
                        `soft` = '',
                        `text` = ?,
                        `screen` = ''
                    ");
                    $stmt->execute([
                        $opis
                    ]);
                } else {
                    echo "Загрузка файла не удалась!<br/>";
                }
            } else {
                echo "Ошибка, файл с таким именем уже существует в данной директории<br/>";
            }
        } else {
            echo "Загрузка по http<br/>";
            echo "<form action='?act=import&amp;cat=" . $cat . "' method='post'>";
            echo
            "Введите URL:<br/><input type='text' name='url' value='http://'/> <br/>Описание: <br/><textarea name='opis'></textarea><br/>Сохранить как(без расширения): <br/><input type='text' name='newn'/><br/>";
            echo "<input type='submit' name='submit' value='Загрузить'/></form><br/>";
        }
    } else {
        echo 'ERROR<br/><a href="?">Back</a><br/>';
    }
} else {
    echo "Нет доступа!";
}
echo '&#187;<a href="?cat=' . $cat . '">' . $lng['back'] . '</a><br/>';
