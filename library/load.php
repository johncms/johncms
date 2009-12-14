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

if ($rights == 5 || $rights >= 6) {
    if ($_GET['id'] == "") {
        echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $id = intval(trim($_GET['id']));
    $typ = mysql_query("select * from `lib` where id='" . $id . "';");
    $ms = mysql_fetch_array($typ);
    if ($id != 0 && $ms['type'] != "cat") {
        echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    if ($ms['ip'] == 0) {
        if (isset ($_POST['submit'])) {
            if (empty ($_POST['name'])) {
                echo "Вы не ввели название!<br/><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            $name = mb_substr($_POST['name'], 0, 50);
            $fname = $_FILES['fail']['name'];
            $ftip = format($fname);
            $ftip = strtolower($ftip);
            if ($fname != "") {
                if (eregi("[^a-z0-9.()+_-]", $fname)) {
                    echo
                    "В названии файла <b>$fname</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='index.php?act=load&amp;id="
                    . $id . "'>Повторить</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                if ((preg_match("/.php/i", $fname)) or (preg_match("/.pl/i", $fname)) or ($fname == ".htaccess")) {
                    echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                if ($ftip != "txt") {
                    echo "Это не текст .txt .<br/><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "temp/$fname")) == true) {
                    $ch = $fname;
                    @ chmod("$ch", 0777);
                    @ chmod("temp/$ch", 0777);
                    $txt = file_get_contents("temp/$ch");
                    if (mb_check_encoding($txt, 'UTF-8')) {
                    }
                    elseif (mb_check_encoding($txt, 'windows-1251')) {
                        $txt = iconv("windows-1251", "UTF-8", $txt);
                    }
                    elseif (mb_check_encoding($txt, 'KOI8-R')) {
                        $txt = iconv("KOI8-R", "UTF-8", $txt);
                    }
                    else {
                        echo "Файл в неизвестной кодировке!<br /><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                        require_once ('../incfiles/end.php');
                        exit;
                    }
                    if (!empty ($_POST['anons'])) {
                        $anons = mb_substr($_POST['anons'], 0, 100);
                    }
                    else {
                        $anons = mb_substr($txt, 0, 100);
                    }
                    mysql_query("insert into `lib` set
							`refid`='" . $id . "',
							`time`='" . $realtime . "',
							`type`='bk',
							`name`='" . mysql_real_escape_string($name) . "',
							`announce`='" .
                    mysql_real_escape_string($anons) . "',
							`avtor`='" . $login . "',
							`text`='" . mysql_real_escape_string($txt) . "',
							`ip`='" . $ipl . "',
							`soft`='" . mysql_real_escape_string($agn) .
                    "',
							`moder`='1';");
                    unlink("temp/$ch");
                    $cid = mysql_insert_id();
                    echo "Статья добавлена<br/><a href='index.php?id=" . $cid . "'>К статье</a><br/>";
                }
                else {
                    echo "Ошибка при загрузке<br/><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
            }
            if (!empty ($_POST['fail1'])) {
                $libedfile = $_POST['fail1'];
                if (strlen($libedfile) > 0) {
                    $array = explode('file=', $libedfile);
                    $tmp_name = $array [0];
                    $filebase64 = $array [1];
                }
                $ftip = strtolower(format($tmp_name));
                if (eregi("[^a-z0-9.()+_-]", $tmp_name)) {
                    echo
                    "В названии файла <b>$fname</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='index.php?act=load&amp;id="
                    . $id . "'>Повторить</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                if ((preg_match("/.php/i", $fname)) or (preg_match("/.pl/i", $tmp_name)) or ($fname == ".htaccess")) {
                    echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                if ($ftip != "txt") {
                    echo "Это не текст .txt .<br/><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                if (strlen($filebase64) > 0) {
                    $FileName = "temp/$tmp_name";
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
                        echo 'Файл загружен!<br/>';
                        $txt = file_get_contents("temp/$tmp_name");
                        if (mb_check_encoding($txt, 'windows-1251')) {
                            $txt = iconv("windows-1251", "UTF-8", $txt);

                        }
                        elseif (mb_check_encoding($txt, 'KOI8-R')) {
                            $txt = iconv("KOI8-R", "UTF-8", $txt);
                        }
                        elseif (mb_check_encoding($txt, 'UTF-8')) {
                            $txt = $txt;
                        }
                        else {
                            echo "Файл в неизвестной кодировке!<br /><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        if (!empty ($_POST['anons'])) {
                            $anons = mb_substr($_POST['anons'], 0, 100);
                        }
                        else {
                            $anons = mb_substr($txt, 0, 100);
                        }
                        mysql_query("INSERT INTO `lib` (
								refid,
								time,
								type,
								name,
								announce,
								text,
								avtor,
								ip,
								soft,
								moder
								) VALUES(
								'" . $id .
                        "',
								'" . $realtime . "',
								'bk',
								'" . $name . "',
								'" . mysql_real_escape_string($anons) . "',
								'" . mysql_real_escape_string($txt) . "',
								'" . $login . "',
								'" . $ipl
                        . "',
								'" . mysql_real_escape_string($agn) . "',
								'1'
								);");
                        unlink("temp/$tmp_name");
                        $cid = mysql_insert_id();
                        echo "Статья добавлена<br/><a href='index.php?id=" . $cid . "'>К статье</a><br/>";
                    }
                    else {
                        echo 'Ошибка при загрузке файла<br/>';
                    }
                }
            }
        }
        else {
            echo "Выгрузка статьи<br/>(Поддерживаются кодировки Win-1251, KOI8-R, UTF-8)<br/><form action='index.php?act=load&amp;id=" . $id .
            "' method='post' enctype='multipart/form-data'>Название статьи (max 50)<br/><input type='text' name='name'/><br/>Анонс (max 100)<br/><input type='text' name='anons'/><br/>Выберите текстовый файл( .txt):<br/><input type='file' name='fail'/><hr/>Для Opera Mini:<br/><input name='fail1' value =''/>&nbsp;<br/>
<a href='op:fileselect'>Выбрать файл</a>
<hr/><input type='submit' name='submit' value='Ok!'/><br/></form><a href ='index.php?id="
            . $id . "'>Назад</a><br/>";
        }
    }
    else {
        echo "Ваще то эта категория не для статей,а для других категорий<br/>";
    }
}
else {
    header("location: index.php");
}

?>