<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_JOHNCMS', 1);

$textl = 'Почта(письма)';
$headmod = 'pradd';
require_once("../incfiles/core.php");
if ($user_id) {
    $msg = functions::check($_POST['msg']);
    if ($_POST['msgtrans'] == 1) {
        $msg = trans($msg);
    }
    $foruser = functions::check($_POST['foruser']);
    $tem = functions::check($_POST['tem']);
    $idm = intval($_POST['idm']);
    switch ($act) {
        case 'send':
            ////////////////////////////////////////////////////////////
            // Отправка письма и обработка прикрепленного файла       //
            ////////////////////////////////////////////////////////////

            // Проверка на спам
            $old = ($rights > 0) ? 10 : 30;
            if ($datauser['lastpost'] > ($realtime - $old)) {
                require_once('../incfiles/head.php');
                echo "<p><b>Антифлуд!</b><br />Вы не можете так часто писать<br/>Порог $old секунд<br/><br/><a href='c'>Назад</a></p>";
                require_once('../incfiles/end.php');
                exit;
            }
            if ($ban['1'] || $ban['3'])
                exit;
            require_once('../incfiles/head.php');
            $ign = mysql_query("select * from `privat` where me='" . $foruser . "' and ignor='" . $login . "';");
            $ign1 = mysql_num_rows($ign);
            if ($ign1 != 0) {
                echo "Вы не можете отправить письмо для $foruser ,поскольку находитесь в его игнор-листе!!!<br/><a href='my_cabinet.php'>В приват</a><br/>";
                require_once('../incfiles/end.php');
                exit;
            }
            if (!empty($foruser) and !empty($msg)) {
                $m = mysql_query("select * from `users` where name='" . $foruser . "';");
                $count = mysql_num_rows($m);
                if ($count == 1) {
                    $messag = mysql_query("select * from `users` where name='" . $foruser . "';");
                    $us = mysql_fetch_array($messag);
                    $adres = $us['id'];
                    // Проверка, был ли выгружен файл и с какого браузера
                    $do_file = false;
                    $do_file_mini = false;
                    // Проверка загрузки с обычного браузера
                    if ($_FILES['fail']['size'] > 0) {
                        $do_file = true;
                        $fname = strtolower($_FILES['fail']['name']);
                        $fsize = $_FILES['fail']['size'];
                    }
                    // Проверка загрузки с Opera Mini
                    elseif (strlen($_POST['fail1']) > 0) {
                        $do_file_mini = true;
                        $array = explode('file=', $_POST['fail1']);
                        $fname = strtolower($array[0]);
                        $filebase64 = $array[1];
                        $fsize = strlen(base64_decode($filebase64));
                    }
                    // Обработка файла (если есть)
                    if ($do_file || $do_file_mini) {
                        // Список допустимых расширений файлов.
                        $al_ext = array (
                            'rar',
                            'zip',
                            'pdf',
                            'txt',
                            'tar',
                            'gz',
                            'jpg',
                            'jpeg',
                            'gif',
                            'png',
                            'bmp',
                            '3gp',
                            'mp3',
                            'mpg',
                            'sis',
                            'thm',
                            'jar',
                            'jad',
                            'cab',
                            'sis',
                            'sisx',
                            'exe',
                            'msi'
                        );
                        $ext = explode(".", $fname);
                        // Проверка на допустимый размер файла
                        if ($fsize >= 1024 * $set['flsz']) {
                            echo '<p><b>ОШИБКА!</b></p><p>Вес файла превышает ' . $set['flsz'] . ' кб.';
                            echo '</p><p><a href="pradd.php?act=write&amp;adr=' . $adres . '">Повторить</a></p>';
                            require_once('../incfiles/end.php');
                            exit;
                        }
                        // Проверка файла на наличие только одного расширения
                        if (count($ext) != 2) {
                            echo '<p><b>ОШИБКА!</b></p><p>Неправильное имя файла!<br />';
                            echo 'К отправке разрешены только файлы имеющие имя и одно расширение (<b>name.ext</b>).<br />';
                            echo 'Запрещены файлы не имеющие имени, расширения, или с двойным расширением.';
                            echo '</p><p><a href="pradd.php?act=write&amp;adr=' . $adres . '">Повторить</a></p>';
                            require_once('../incfiles/end.php');
                            exit;
                        }
                        // Проверка допустимых расширений файлов
                        if (!in_array($ext[1], $al_ext)) {
                            echo '<p><b>ОШИБКА!</b></p><p>Запрещенный тип файла!<br />';
                            echo 'К отправке разрешены только файлы, имеющие следующее расширение:<br />';
                            echo implode(', ', $al_ext);
                            echo '</p><p><a href="pradd.php?act=write&amp;adr=' . $adres . '">Повторить</a></p>';
                            require_once('../incfiles/end.php');
                            exit;
                        }
                        // Проверка на длину имени
                        if (strlen($fname) > 30) {
                            echo '<p><b>ОШИБКА!</b></p><p>Длина названия файла не должна превышать 30 символов!';
                            echo '</p><p><a href="pradd.php?act=write&amp;adr=' . $adres . '">Повторить</a></p>';
                            require_once('../incfiles/end.php');
                            exit;
                        }
                        // Проверка на запрещенные символы
                        if (eregi("[^a-z0-9.()+_-]", $fname)) {
                            echo '<p><b>ОШИБКА!</b></p><p>В названии файла "<b>' . $fname . '</b>" присутствуют недопустимые символы.<br />';
                            echo 'Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br />Запрещены пробелы.';
                            echo '</p><p><a href="pradd.php?act=write&amp;adr=' . $adres . '">Повторить</a></p>';
                            require_once('../incfiles/end.php');
                            exit;
                        }
                        // Проверка наличия файла с таким же именем
                        if (file_exists("../files/users/pm/$fname")) {
                            $fname = $realtime . $fname;
                        }
                        // Окончательная обработка
                        if ($do_file) {
                            // Для обычного браузера
                            if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "../files/users/pm/$fname")) == true) {
                                @chmod("$fname", 0777);
                                @chmod("../files/users/pm/$fname", 0777);
                                echo 'Файл прикреплен!<br/>';
                            } else {
                                echo 'Ошибка прикрепления файла.<br/>';
                            }
                        } elseif ($do_file_mini) {
                            // Для Opera Mini
                            if (strlen($filebase64) > 0) {
                                $FileName = "../files/users/pm/$fname";
                                $filedata = base64_decode($filebase64);
                                $fid = @fopen($FileName, "wb");
                                if ($fid) {
                                    if (flock($fid, LOCK_EX)) {
                                        fwrite($fid, $filedata);
                                        flock($fid, LOCK_UN);
                                    }
                                    fclose($fid);
                                }
                                if (file_exists($FileName) && filesize($FileName) == strlen($filedata)) {
                                    echo 'Файл прикреплён.<br/>';
                                } else {
                                    echo 'Ошибка прикрепления файла.<br/>';
                                }
                            }
                        }
                    }
                    mysql_query("insert into `privat` values(0,'" . $foruser . "','" . $msg . "','" . $realtime . "','" . $login . "','in','no','" . $tem . "','0','','','','" . mysql_real_escape_string($fname) . "');");
                    mysql_query("insert into `privat` values(0,'" . $foruser . "','" . $msg . "','" . $realtime . "','" . $login . "','out','no','" . $tem . "','0','','','','" . mysql_real_escape_string($fname) . "');");
                    if (!empty($idm)) {
                        mysql_query("update `privat` set otvet='1' where id='" . $idm . "';");
                    }
                    mysql_query("UPDATE `users` SET `lastpost` = '" . $realtime . "' WHERE `id` = '" . $user_id . "'");
                    echo "<p>Письмо отправлено!</p>";
                    if (!empty($_SESSION['refpr'])) {
                        echo "<a href='" . $_SESSION['refpr'] . "'>Вернуться откуда пришли</a><br/>";
                    }
                    $_SESSION['refpr'] = "";
                } else {
                    echo "Такого пользователя не существует<br/>";
                }
            } else {
                echo "Не введено имя пользователя или сообщение!<br/>";
            }
            break;

        case 'load':
            ////////////////////////////////////////////////////////////
            // Скачивание файла                                       //
            ////////////////////////////////////////////////////////////
            $id = intval($_GET['id']);
            $fil = mysql_query("select * from `privat` where id='" . $id . "';");
            $mas = mysql_fetch_array($fil);
            $att = $mas['attach'];
            if (!empty($att)) {
                $tfl = strtolower(functions::format(trim($att)));
                $df = array (
                    "asp",
                    "aspx",
                    "shtml",
                    "htd",
                    "php",
                    "php3",
                    "php4",
                    "php5",
                    "phtml",
                    "htt",
                    "cfm",
                    "tpl",
                    "dtd",
                    "hta",
                    "pl",
                    "js",
                    "jsp"
                );
                if (in_array($tfl, $df)) {
                    require_once('../incfiles/head.php');
                    echo "Ошибка!<br/>&#187;<a href='pradd.php'>В приват</a><br/>";
                    require_once('../incfiles/end.php');
                    exit;
                }
                if (file_exists("../files/users/pm/$att")) {
                    header("location: ../files/users/pm/$att");
                }
            }
            break;

        case 'write':
            ////////////////////////////////////////////////////////////
            // Форма для отправки привата                             //
            ////////////////////////////////////////////////////////////
            if ($ban['1'] || $ban['3'])
                exit;
            // Проверка на спам
            $old = ($rights > 0) ? 10 : 30;
            if ($datauser['lastpost'] > ($realtime - $old)) {
                require_once('../incfiles/head.php');
                echo "<p><b>Антифлуд!</b><br />Вы не можете так часто писать<br/>Порог $old секунд<br/><br/><a href='my_cabinet.php'>Назад</a></p>";
                require_once('../incfiles/end.php');
                exit;
            }
            require_once('../incfiles/head.php');
            if (!empty($_GET['adr'])) {
                $messages = mysql_query("select * from `users` where id='" . intval($_GET['adr']) . "';");
                $user = mysql_fetch_array($messages);
                $adresat = $user['name'];
                $tema = "Привет, $adresat!";
                $ign = mysql_query("select * from `privat` where me='" . $adresat . "' and ignor='" . $login . "';");
                $ign1 = mysql_num_rows($ign);
                if ($ign1 != 0) {
                    echo "Вы не можете отправить письмо для $adresat ,поскольку находитесь в его игнор-листе!!!<br/><a href='my_cabinet.php'>В приват</a><br/>";
                    require_once('../incfiles/end.php');
                    exit;
                }
            } else {
                $tema = "Привет!";
            }
            if (!empty($_GET['id'])) {
                $id = intval($_GET['id']);
                $messages2 = mysql_query("select * from `privat` where id='" . $id . "';");
                $tm = mysql_fetch_array($messages2);
                $thm = $tm['temka'];
                if (stristr($thm, "Re:")) {
                    $thm = str_replace("Re:", "", $thm);
                    $tema = "Re[1]: $thm";
                } elseif (stristr($thm, "Re[")) {
                    $t1 = str_replace("Re[", "", $thm);
                    $t1 = strtok($t1, "]");
                    $t1 = $t1 + 1;
                    $o = explode(" ", $thm);
                    $thm = str_replace("$o[0]", "", $thm);
                    $tema = "Re[$t1]:$thm";
                } else {
                    $tema = "Re: $thm";
                }
            }
            if (isset($_GET['bir'])) {
                $tema = "С Днём Рождения!!!";
            }
            echo "Написать письмо<br/>";
            echo "<form action='pradd.php?act=send' method='post' enctype='multipart/form-data'>Для:";
            if (!empty($_GET['adr'])) {
                echo " $adresat<br/>";
                echo "<input type='hidden' name='foruser' value='" . $adresat . "'/>";
            } else {
                echo "<br/><input type='text' name='foruser'/>";
            }
            echo " <br/>Тема:<br/><input type='text' name='tem' value='" . $tema .
                "'/><br/> Cообщение:<br/><textarea rows='5' name='msg'></textarea><br/>Прикрепить файл(max. " . $set['flsz'] . " kb):<br/><input type='file' name='fail'/><hr/>Прикрепить файл(Opera Mini):<br/><input name='fail1' value =''/>&#160;<br/>
<a href='op:fileselect'>Выбрать файл</a><hr/>";
            if ($set_user['translit'])
                echo '<input type="checkbox" name="msgtrans" value="1" /> Транслит сообщения<br/>';
            echo "<input type='hidden' name='idm' value='" . $id . "'/><input type='submit' value='Отправить' /></form>";
            echo '<a href="pradd.php?act=trans">Транслит</a><br/><a href="smile.php">' . $lng['smileys'] . '</a><br/>';
            break;

        case 'delch':
            ////////////////////////////////////////////////////////////
            // Удаление выбранных писем                               //
            ////////////////////////////////////////////////////////////
            require_once('../incfiles/head.php');
            if (isset($_GET['yes'])) {
                $dc = $_SESSION['dc'];
                $prd = $_SESSION['prd'];
                foreach ($dc as $delid) {
                    mysql_query("DELETE FROM `privat` WHERE (`user` = '$login' OR `author` = '$login') AND `id`='" . intval($delid) . "'");
                }
                echo "Отмеченные письма удалены<br/><a href='" . $prd . "'>Назад</a><br/>";
            } else {
                if (empty($_POST['delch'])) {
                    echo "Вы не выбрали писем для удаления<br/><a href='pradd.php?act=in'>Назад</a><br/>";
                    require_once('../incfiles/end.php');
                    exit;
                }
                foreach ($_POST['delch'] as $v) {
                    $dc[] = intval($v);
                }
                $_SESSION['dc'] = $dc;
                $_SESSION['prd'] = htmlspecialchars(getenv("HTTP_REFERER"));
                echo "Вы уверены в удалении писем?<br/><a href='pradd.php?act=delch&amp;yes'>Да</a> | <a href='" . htmlspecialchars(getenv("HTTP_REFERER")) . "'>Нет</a><br/>";
            }
            break;

        case 'in':
            ////////////////////////////////////////////////////////////
            // Список входящих писем                                  //
            ////////////////////////////////////////////////////////////
            require_once('../incfiles/head.php');
            if (isset($_GET['new'])) {
                $_SESSION['refpr'] = htmlspecialchars(getenv("HTTP_REFERER"));
                $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `user` = '$login' AND `type` = 'in' AND `chit` = 'no'"), 0);
                $req = mysql_query("SELECT * FROM `privat` WHERE `user` = '$login' AND `type` = 'in' AND `chit` = 'no' ORDER BY `id` DESC LIMIT $start,$kmess");
                echo '<div class="phdr">Новые входящие</div>';
            } else {
                $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `user` = '$login' AND `type` = 'in'"), 0);
                $req = mysql_query("SELECT * FROM `privat` WHERE `user` = '$login' AND `type` = 'in' ORDER BY `id` DESC LIMIT $start,$kmess");
                echo '<div class="phdr"><b>Входящие письма</b></div>';
            }
            echo '<form action="pradd.php?act=delch" method="post">';
            while ($res = mysql_fetch_assoc($req)) {
                if ($res['chit'] == "no") {
                    echo '<div class="gmenu">';
                } else {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                }
                echo '<input type="checkbox" name="delch[]" value="' . $res['id'] . '"/><a href="pradd.php?id=' . $res['id'] . '&amp;act=readmess">От: ' . $res['author'] . '</a>';
                $vrp = $res['time'] + $set_user['sdvig'] * 3600;
                echo '&#160;<span class="gray">(' . date("d.m.y H:i", $vrp) . ')<br/>Тема:</span> ' . $res['temka'] . '<br/>';
                if (!empty($res['attach'])) {
                    echo "+ вложение<br/>";
                }
                if ($res['otvet'] == 0) {
                    echo "Не отвечено<br/>";
                }
                echo '</div>';
                ++$i;
            }
            if ($total > 0) {
                echo '<div class="rmenu"><input type="submit" value="Удалить отмеченные"/></div>';
            }
            echo '</form>';
            echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
            if ($total > $kmess) {
                echo '<p>' . functions::display_pagination('pradd.php?act=in&amp;', $start, $total, $kmess) . '</p>';
                echo '<p><form action="pradd.php?act=in" method="post"><input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
            }
            if ($total > 0) {
                echo "<a href='pradd.php?act=delread'>Удалить прочитанные</a><br/>";
                echo "<a href='pradd.php?act=delin'>Удалить все входящие</a><br/>";
            }
            break;

        case 'delread':
            ////////////////////////////////////////////////////////////
            // Удаление прочитанных писем                             //
            ////////////////////////////////////////////////////////////
            require_once('../incfiles/head.php');
            $mess1 = mysql_query("select * from `privat` where user='" . $login . "' and type='in' and chit='yes';");
            while ($mas1 = mysql_fetch_array($mess1)) {
                $delid = $mas1['id'];
                $delfile = $mas1['attach'];
                if (!empty($delfile)) {
                    if (file_exists("../files/users/pm/$delfile")) {
                        unlink("../files/users/pm/$delfile");
                    }
                }
                mysql_query("delete from `privat` where `id`='" . intval($delid) . "';");
            }
            echo "Прочитанные письма удалены<br/>";
            break;

        case 'delin':
            ////////////////////////////////////////////////////////////
            // Удаление всех входящих писем                           //
            ////////////////////////////////////////////////////////////
            require_once('../incfiles/head.php');
            $mess1 = mysql_query("select * from `privat` where user='$login' and type='in'");
            while ($mas1 = mysql_fetch_array($mess1)) {
                $delfile = $mas1['attach'];
                if (!empty($delfile)) {
                    if (file_exists("../files/users/pm/$delfile")) {
                        unlink("../files/users/pm/$delfile");
                    }
                }
            }
            mysql_query("DELETE FROM `privat` WHERE `user` = '$login' AND `type` = 'in'");
            echo "Входящие письма удалены<br/>";
            break;

        case 'readmess':
            ////////////////////////////////////////////////////////////
            // Читаем входящие письма                                 //
            ////////////////////////////////////////////////////////////
            require_once('../incfiles/head.php');
            $messages1 = mysql_query("select * from `privat` where user='" . $login . "' and type='in' and id='" . $id . "';");
            $massiv1 = mysql_fetch_array($messages1);
            if ($massiv1['chit'] == "no") {
                mysql_query("update `privat` set `chit`='yes' where `id`='" . $massiv1['id'] . "';");
            }
            $newl = mysql_query("select * from `privat` where user = '" . $login . "' and type = 'in' and chit = 'no';");
            $countnew = mysql_num_rows($newl);
            if ($countnew > 0) {
                echo "<div style='text-align: center'><a href='" . $set['homeurl'] . "/users/pradd.php?act=in&amp;new'><b><font color='red'>Вам письмо: $countnew</font></b></a></div>";
            }
            $mass = mysql_fetch_array(mysql_query("select * from `users` where `name`='" . $massiv1['author'] . "';"));
            $text = $massiv1['text'];
            $text = tags($text);
            if ($set_user['smileys'])
                $text = functions::smileys($text, ($massiv1['from'] == $nickadmina || $massiv1['from'] == $nickadmina2 || $massiv11['rights'] >= 1) ? 1 : 0);
            echo "<p>От <a href='profile.php?user=" . $mass['id'] . "'>$massiv1[author]</a><br/>";
            $vrp = $massiv1['time'] + $set_user['sdvig'] * 3600;
            echo "(" . date("d.m.y H:i", $vrp) . ")</p><p><div class='b'>Тема: $massiv1[temka]<br/></div>Текст: $text</p>";
            if (!empty($massiv1['attach'])) {
                echo "<p>Прикреплённый файл: <a href='?act=load&amp;id=" . $id . "'>$massiv1[attach]</a></p>";
            }
            echo "<hr /><p><a href='pradd.php?act=write&amp;adr=" . $mass['id'] . "&amp;id=" . $massiv1['id'] . "'>Ответить</a><br/><a href='pradd.php?act=delmess&amp;del=" . $massiv1['id'] . "'>Удалить</a></p>";
            $mas2 = mysql_fetch_array(@mysql_query("select * from `privat` where `time`='$massiv1[time]' and author='$massiv1[author]' and type='out';"));
            if ($mas2['chit'] == "no") {
                mysql_query("update `privat` set `chit`='yes' where `id`='" . $mas2['id'] . "';");
            }
            if ($massiv1['chit'] == "no") {
                mysql_query("update `privat` set `chit`='yes' where `id`='" . $massiv1['id'] . "';");
            }
            break;

        case 'delmess':
            ////////////////////////////////////////////////////////////
            // Удаление отдельного сообщения                          //
            ////////////////////////////////////////////////////////////
            require_once('../incfiles/head.php');
            $mess1 = mysql_query("SELECT * FROM `privat` WHERE `user` = '$login' AND `id` = '" . intval($_GET['del']) . "' LIMIT 1");
            $mas1 = mysql_fetch_array($mess1);
            $delfile = $mas1['attach'];
            if (!empty($delfile)) {
                if (file_exists("../files/users/pm/$delfile")) {
                    unlink("../files/users/pm/$delfile");
                }
            }
            mysql_query("DELETE FROM `privat` WHERE (`user` = '$login' OR `author` = '$login') AND `id` = '" . intval($_GET['del']) . "' LIMIT 1");
            echo 'Сообщение удалено!<br/>';
            break;

        case 'delout':
            ////////////////////////////////////////////////////////////
            // Удаление отправленных писем                            //
            ////////////////////////////////////////////////////////////
            require_once('../incfiles/head.php');
            $mess1 = mysql_query("select * from `privat` where author='$login' and type='out';");
            while ($mas1 = mysql_fetch_array($mess1)) {
                $delid = $mas1['id'];
                mysql_query("delete from `privat` where `id`='" . intval($delid) . "';");
            }
            echo "Исходящие письма удалены<br/>";
            break;

        case 'out':
            ////////////////////////////////////////////////////////////
            // Список отправленных                                    //
            ////////////////////////////////////////////////////////////
            require_once('../incfiles/head.php');
            $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `author` = '$login' AND `type` = 'out'"), 0);
            $req = mysql_query("SELECT * FROM `privat` WHERE `author` = '$login' AND `type` = 'out' ORDER BY `id` DESC LIMIT $start,$kmess");
            echo '<div class="phdr"><b>Отправленные письма</b></div>';
            echo "<form action='pradd.php?act=delch' method='post'>";
            while ($res = mysql_fetch_assoc($req)) {
                if ($res['chit'] == "no") {
                    echo '<div class="gmenu">';
                } else {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                }
                echo '<input type="checkbox" name="delch[]" value="' . $res['id'] . '"/>Для: <a href="pradd.php?id=' . $res['id'] . '&amp;act=readout">' . $res['user'] . '</a>';
                $vrp = $res['time'] + $set_user['sdvig'] * 3600;
                echo '&#160;<span class="gray">(' . date("d.m.y H:i", $vrp) . ')<br/>Тема:</span> ' . $res['temka'] . '<br/>';
                if (!empty($res['attach'])) {
                    echo "+ вложение<br/>";
                }
                echo '</div>';
                ++$i;
            }
            if ($total > 0) {
                echo '<div class="rmenu"><input type="submit" value="Удалить отмеченные"/></div>';
            }
            echo '</form>';
            echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
            if ($total > $kmess) {
                echo '<p>' . functions::display_pagination('pradd.php?act=out&amp;', $start, $total, $kmess) . '</p>';
                echo '<p><form action="pradd.php?act=out" method="post"><input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
            }
            if ($total > 0) {
                echo "<a href='pradd.php?act=delout'>Удалить все исходящие</a><br/>";
            }
            break;

        case 'readout':
            ////////////////////////////////////////////////////////////
            // Читаем исходящие письма                                //
            ////////////////////////////////////////////////////////////
            require_once('../incfiles/head.php');
            $messages1 = mysql_query("select * from `privat` where author='" . $login . "' and type='out' and id='" . $id . "';");
            $massiv1 = mysql_fetch_array($messages1);
            $mass = mysql_fetch_array(@mysql_query("select * from `users` where `name`='$massiv1[user]';"));
            $text = $massiv1['text'];
            $text = tags($text);
            if ($set_user['smileys'])
                $text = functions::smileys($text, ($massiv1['from'] == $nickadmina || $massiv1['from'] == $nickadmina2 || $massiv11['rights'] >= 1) ? 1 : 0);
            echo "<p>Для <a href='profile.php?user=" . $mass['id'] . "'>$massiv1[user]</a><br/>";
            $vrp = $massiv1['time'] + $set_user['sdvig'] * 3600;
            echo "(" . date("d.m.y H:i", $vrp) . ")</p><p><div class='b'>Тема: $massiv1[temka]<br/></div>Текст: $text</p>";
            if (!empty($massiv1['attach'])) {
                echo "<p>Прикреплённый файл: $massiv1[attach]</p>";
            }
            echo "<hr /><p><a href='pradd.php?act=delmess&amp;del=" . $massiv1['id'] . "'>Удалить</a></p>";
            break;
    }
    echo "<p><a href='profile.php?act=office'>В кабинет</a><br/>";
    echo "<a href='pradd.php?act=write'>Написать</a></p>";
}

require_once('../incfiles/end.php');

?>
