<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                                                                    //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@johncms.com                     //
// Олег Касьянов aka AlkatraZ          alkatraz@johncms.com                   //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

require_once ("../incfiles/head.php");
if (!$id || !$user_id) {
    echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
// Проверяем, тот ли юзер заливает файл
$req = mysql_query("SELECT * FROM `forum` WHERE `id`= '" . $id . "' LIMIT 1");
$res = mysql_fetch_array($req);
if ($res['from'] != $login) {
    echo '<p>ОШИБКА!</p>';
    require_once ("../incfiles/end.php");
    exit;
}
$req1 = mysql_query("SELECT COUNT(*) FROM `cms_forum_files` WHERE `post` = '" . $id . "'");
if (mysql_result($req1, 0) > 0) {
    echo '<p>ОШИБКА!<br />Файл уже загружен</p>';
    require_once ("../incfiles/end.php");
    exit;
}
switch ($res['type']) {
    case "m" :
        if (isset ($_POST['submit'])) {
            ////////////////////////////////////////////////////////////
            // Проверка, был ли выгружен файл и с какого браузера     //
            ////////////////////////////////////////////////////////////
            $do_file = false;
            $do_file_mini = false;
            if ($_FILES['fail']['size'] > 0) {
                // Проверка загрузки с обычного браузера
                $do_file = true;
                $fname = strtolower($_FILES['fail']['name']);
                $fsize = $_FILES['fail']['size'];
            }
            elseif (strlen($_POST['fail1']) > 0) {
                // Проверка загрузки с Opera Mini
                $do_file_mini = true;
                $array = explode('file=', $_POST['fail1']);
                $fname = strtolower($array [0]);
                $filebase64 = $array [1];
                $fsize = strlen(base64_decode($filebase64));
            }
            ////////////////////////////////////////////////////////////
            // Обработка файла (если есть)                            //
            ////////////////////////////////////////////////////////////
            if ($do_file || $do_file_mini) {
                // Список допустимых расширений файлов.
                $al_ext = array_merge($ext_win, $ext_java, $ext_sis, $ext_doc, $ext_pic, $ext_zip, $ext_video, $ext_audio, $ext_other);
                $ext = explode(".", $fname);
                // Проверка на допустимый размер файла
                if ($fsize > 1024 * $flsz) {
                    echo '<p><b>ОШИБКА!</b></p><p>Вес файла превышает ' . $flsz . ' кб.';
                    echo '</p><p><a href="index.php?act=addfile&amp;id=' . $id . '">Повторить</a></p>';
                    require_once ('../incfiles/end.php');
                    exit;
                }
                // Проверка файла на наличие только одного расширения
                if (count($ext) != 2) {
                    echo '<p><b>ОШИБКА!</b></p><p>Неправильное имя файла!<br />';
                    echo 'К отправке разрешены только файлы имеющие имя и одно расширение (<b>name.ext</b>).<br />';
                    echo 'Запрещены файлы не имеющие имени, расширения, или с двойным расширением.';
                    echo '</p><p><a href="index.php?act=addfile&amp;id=' . $id . '">Повторить</a></p>';
                    require_once ('../incfiles/end.php');
                    exit;
                }
                // Проверка допустимых расширений файлов
                if (!in_array($ext[1], $al_ext)) {
                    echo '<p><b>ОШИБКА!</b></p><p>Запрещенный тип файла!<br />';
                    echo 'К отправке разрешены только файлы, имеющие следующее расширение:<br />';
                    echo implode(', ', $al_ext);
                    echo '</p><p><a href="index.php?act=addfile&amp;id=' . $id . '">Повторить</a></p>';
                    require_once ('../incfiles/end.php');
                    exit;
                }
                // Проверка на длину имени
                if (strlen($fname) > 30) {
                    echo '<p><b>ОШИБКА!</b></p><p>Длина названия файла не должна превышать 30 символов!';
                    echo '</p><p><a href="index.php?act=addfile&amp;id=' . $id . '">Повторить</a></p>';
                    require_once ('../incfiles/end.php');
                    exit;
                }
                // Проверка на запрещенные символы
                if (eregi("[^a-z0-9.()+_-]", $fname)) {
                    echo '<p><b>ОШИБКА!</b></p><p>В названии файла присутствуют недопустимые символы.<br />';
                    echo 'Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br />Запрещены пробелы.';
                    echo '</p><p><a href="index.php?act=addfile&amp;id=' . $id . '">Повторить</a></p>';
                    require_once ('../incfiles/end.php');
                    exit;
                }
                // Проверка наличия файла с таким же именем
                if (file_exists("files/$fname")) {
                    $fname = $realtime . $fname;
                }
                // Окончательная обработка
                if ($do_file) {
                    // Для обычного браузера
                    if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "files/$fname")) == true) {
                        @ chmod("$fname", 0777);
                        @ chmod("files/$fname", 0777);
                        echo 'Файл прикреплен!<br/>';
                    }
                    else {
                        echo 'Ошибка прикрепления файла.<br/>';
                    }
                }
                elseif ($do_file_mini) {
                    // Для Opera Mini
                    if (strlen($filebase64) > 0) {
                        $FileName = "files/$fname";
                        $filedata = base64_decode($filebase64);
                        $fid = @ fopen($FileName, "wb");
                        if ($fid) {
                            if (flock($fid, LOCK_EX)) {
                                fwrite($fid, $filedata);
                                flock($fid, LOCK_UN);
                            }
                            fclose($fid);
                        }
                        if (file_exists($FileName) && filesize($FileName) == strlen($filedata)) {
                            echo 'Файл прикреплён.<br/>';
                        }
                        else {
                            echo 'Ошибка прикрепления файла.<br/>';
                        }
                    }
                }
                // Определяем тип файла
                $ext = strtolower($ext[1]);
                if (in_array($ext, $ext_win))
                    $type = 1;
                elseif (in_array($ext, $ext_java))
                    $type = 2;
                elseif (in_array($ext, $ext_sis))
                    $type = 3;
                elseif (in_array($ext, $ext_doc))
                    $type = 4;
                elseif (in_array($ext, $ext_pic))
                    $type = 5;
                elseif (in_array($ext, $ext_zip))
                    $type = 6;
                elseif (in_array($ext, $ext_video))
                    $type = 7;
                elseif (in_array($ext, $ext_audio))
                    $type = 8;
                else
                    $type = 9;
                // Определяем ID субкатегории и категории
                $req2 = mysql_query("SELECT * FROM `forum` WHERE `id` = '" . $res['refid'] . "' LIMIT 1");
                $res2 = mysql_fetch_array($req2);
                $req3 = mysql_query("SELECT * FROM `forum` WHERE `id` = '" . $res2['refid'] . "' LIMIT 1");
                $res3 = mysql_fetch_array($req3);
                // Заносим данные в базу
                mysql_query("INSERT INTO `cms_forum_files` SET
				`cat` = '" . $res3['refid'] . "',
				`subcat` = '" . $res2['refid'] . "',
				`topic` = '" . $res['refid'] . "',
				`post` = '$id',
				`time` = '" . $res['time'] .
                "',
				`filename` = '" . mysql_real_escape_string($fname) . "',
				`filetype` = '$type'");
            }
            else {
                echo 'Ошибка передачи файла<br />';
            }
            $pa = mysql_query("SELECT `id` FROM `forum` WHERE `type` = 'm' AND `refid` = '" . $res['refid'] . "'");
            $pa2 = mysql_num_rows($pa);
            $page = ceil($pa2 / $kmess);
            echo "<br/><a href='index.php?id=" . $res['refid'] . "&amp;page=" . $page . "'>Продолжить</a><br/>";
        }
        else {
            echo '<div class="phdr"><b>Добавление файла</b></div>';
            echo '<div class="gmenu"><form action="index.php?act=addfile&amp;id=' . $id . '" method="post" enctype="multipart/form-data"><p>';
            if (!eregi("Opera/8.01", $agent)) {
                echo '<input type="file" name="fail"/>';
            }
            else {
                echo '<input name="fail1" value =""/>&nbsp;<br/><a href="op:fileselect">Выбрать файл</a>';
            }
            echo '</p><p><input type="submit" name="submit" value="Отправить"/></p></form></div>';
            echo '<div class="phdr">Макс. размер: ' . $flsz . 'kb</div>';
        }
        break;

    default :
        echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
        break;
}

?>