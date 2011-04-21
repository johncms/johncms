<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

define('_IN_JOHNCMS', 1);

$textl = 'Mail';
$headmod = 'pradd';
require_once("../incfiles/core.php");
$lng_pm = $core->load_lng('pm');
if ($user_id) {
    $msg = isset($_POST['msg']) ? functions::check($_POST['msg']) : false;
    if (isset($_POST['msgtrans'])) {
        $msg = functions::trans($msg);
    }
    $foruser = isset($_POST['foruser']) ? functions::check($_POST['foruser']) : false;
    $tem = isset($_POST['tem']) ? functions::check($_POST['tem']) : false;
    $idm = isset($_POST['idm']) ? intval($_POST['idm']) : false;
    switch ($act) {
        case 'send':
            ////////////////////////////////////////////////////////////
            // Отправка письма и обработка прикрепленного файла       //
            ////////////////////////////////////////////////////////////

            if (isset($ban['1']) || isset($ban['3']))
                exit;

            // Проверка на флуд
            $flood = functions::antiflood();
            if ($flood) {
                echo functions::display_error($lng['error_flood'] . ' ' . $flood . '&#160;' . $lng['seconds'], '<a href="my_cabinet.php">' . $lng['back'] . '</a>');
                require_once('../incfiles/end.php');
                exit;
            }
            require_once('../incfiles/head.php');
            $ign = mysql_query("select * from `privat` where me='" . $foruser . "' and ignor='" . $login . "';");
            $ign1 = mysql_num_rows($ign);
            if ($ign1 != 0) {
                echo functions::display_error($lng_pm['error_ignor'], '<a href="my_cabinet.php">' . $lng['back'] . '</a>');
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
                        $al_ext = array(
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
                            echo functions::display_error($lng_pm['error_file_size'] . ' ' . $set['flsz'] . 'kB', '<a href="pradd.php?act=write&amp;adr=' . $adres . '">' . $lng['repeat'] . '</a>');
                            require_once('../incfiles/end.php');
                            exit;
                        }
                        // Проверка файла на наличие только одного расширения
                        if (count($ext) != 2) {
                            echo functions::display_error($lng_pm['error_file_ext'], '<a href="pradd.php?act=write&amp;adr=' . $adres . '">' . $lng['repeat'] . '</a>');
                            require_once('../incfiles/end.php');
                            exit;
                        }
                        // Проверка допустимых расширений файлов
                        if (!in_array($ext[1], $al_ext)) {
                            echo functions::display_error($lng_pm['error_file_type'] . '<br />' . implode(', ', $al_ext), '<a href="pradd.php?act=write&amp;adr=' . $adres . '">' . $lng['repeat'] . '</a>');
                            require_once('../incfiles/end.php');
                            exit;
                        }
                        // Проверка на длину имени
                        if (strlen($fname) > 30) {
                            echo functions::display_error($lng_pm['error_file_length'], '<a href="pradd.php?act=write&amp;adr=' . $adres . '">' . $lng['repeat'] . '</a>');
                            require_once('../incfiles/end.php');
                            exit;
                        }
                        // Проверка на запрещенные символы
                        if (eregi("[^a-z0-9.()+_-]", $fname)) {
                            echo functions::display_error($lng_pm['error_file_symbols'], '<a href="pradd.php?act=write&amp;adr=' . $adres . '">' . $lng['repeat'] . '</a>');
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
                                echo $lng_pm['file_attached'] . '<br/>';
                            } else {
                                echo functions::display_error($lng_pm['error_file_attach']);
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
                                    echo $lng_pm['file_attached'] . '<br/>';
                                } else {
                                    echo functions::display_error($lng_pm['error_file_attach']);
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
                    echo '<p>' . $lng_pm['message_sent'] . '</p>';
                    if (!empty($_SESSION['refpr'])) {
                        echo "<a href='" . $_SESSION['refpr'] . "'>" . $lng_pm['back'] . "</a><br/>";
                    }
                    $_SESSION['refpr'] = "";
                } else {
                    echo $lng['error_user_not_exist'] . '<br/>';
                }
            } else {
                echo  $lng['error_empty_fields'] . "<br/>";
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
                $df = array(
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
                    echo "ERROR!<br/>&#187;<a href='pradd.php'>" . $lng['back'] . "</a><br/>";
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
            if (isset($ban['1']) || isset($ban['3']))
                exit;
            require_once('../incfiles/head.php');
            // Проверка на спам
            $flood = functions::antiflood();
            if ($flood) {
                echo functions::display_error($lng['error_flood'] . ' ' . $flood . '&#160;' . $lng['seconds'], '<a href="my_cabinet.php">' . $lng['back'] . '</a>');
                require_once('../incfiles/end.php');
                exit;
            }
            $adresat = '';
            if (!empty($_GET['adr'])) {
                $messages = mysql_query("select * from `users` where id='" . intval($_GET['adr']) . "';");
                $user = mysql_fetch_array($messages);
                $adresat = $user['name'];
                $tema = $lng_pm['hi'] . ', ' . $adresat;
                $ign = mysql_query("select * from `privat` where me='" . $adresat . "' and ignor='" . $login . "'");
                $ign1 = mysql_num_rows($ign);
                if ($ign1 != 0) {
                    echo $lng_pm['error_ignor'] . '<br/><a href="my_cabinet.php">' . $lng['back'] . '</a><br/>';
                    require_once('../incfiles/end.php');
                    exit;
                }
            } else {
                $tema = $lng_pm['hi'] . '!';
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
                $tema = $lng['happy_birthday'];
            }
            echo '<div class="phdr"><b>' . $lng_pm['write_message'] . '</b></div>';
            echo '<form name="form" action="pradd.php?act=send" method="post" enctype="multipart/form-data">' .
                '<div class="menu">' .
                '<p><h3>' . $lng_pm['to'] . '</h3>' .
                '<input type="text" name="foruser" value="' . $adresat . '"/></p>' .
                '<p><h3>' . $lng_pm['subject'] . '</h3>' .
                '<input type="text" name="tem" value="' . $tema . '"/></p>' .
                '<p><h3>' . $lng['message'] . '</h3>' . functions::auto_bb('form', 'msg') .
                '<textarea cols="' . $set_user['field_w'] . '" rows="' . $set_user['field_h'] . '" name="msg"></textarea></p>' .
                '<p><h3>' . $lng_pm['attach_file'] . '</h3>' .
                '<input type="file" name="fail"/><br /><small>max.' . $set['flsz'] . 'kb</small></p>';
            if ($set_user['translit'])
                echo '<p><input type="checkbox" name="msgtrans" value="1" />&#160;' . $lng['translit'] . '</p>';
            echo "<input type='hidden' name='idm' value='" . $id . "'/><p><input type='submit' value='" . $lng['sent'] . "' /></p></div></form>";
            echo '<div class="phdr"><a href="../pages/faq.php?act=trans">' . $lng['translit'] . '</a> | ' .
            '<a href="../pages/faq.php?act=smileys">' . $lng['smileys'] . '</a></div>';
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
                echo $lng_pm['selected_msg_deleted'] . "<br/><a href='" . $prd . "'>" . $lng['back'] . "</a><br/>";
            } else {
                if (empty($_POST['delch'])) {
                    echo $lng_pm['error_not_secected'] . "<br/><a href='pradd.php?act=in'>" . $lng['back'] . "</a><br/>";
                    require_once('../incfiles/end.php');
                    exit;
                }
                foreach ($_POST['delch'] as $v) {
                    $dc[] = intval($v);
                }
                $_SESSION['dc'] = $dc;
                $_SESSION['prd'] = htmlspecialchars(getenv("HTTP_REFERER"));
                echo $lng['delete_confirmation'] . "<br/><a href='pradd.php?act=delch&amp;yes'>" . $lng['delete'] . "</a> | <a href='" . htmlspecialchars(getenv("HTTP_REFERER")) . "'>" . $lng['cancel'] . "</a><br/>";
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
                echo '<div class="phdr">' . $lng_pm['new_incoming'] . '</div>';
            } else {
                $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `user` = '$login' AND `type` = 'in'"), 0);
                $req = mysql_query("SELECT * FROM `privat` WHERE `user` = '$login' AND `type` = 'in' ORDER BY `id` DESC LIMIT $start,$kmess");
                echo '<div class="phdr"><b>' . $lng_pm['incoming'] . '</b></div>';
            }
            echo '<form action="pradd.php?act=delch" method="post">';
            $i = 0;
            while ($res = mysql_fetch_assoc($req)) {
                if ($res['chit'] == "no") {
                    echo '<div class="gmenu">';
                } else {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                }
                echo '<input type="checkbox" name="delch[]" value="' . $res['id'] . '"/><a href="pradd.php?id=' . $res['id'] . '&amp;act=readmess">От: ' . $res['author'] . '</a>';
                $vrp = $res['time'] + $set_user['sdvig'] * 3600;
                echo '&#160;<span class="gray">(' . date("d.m.y H:i", $vrp) . ')<br/>' . $lng_pm['subject'] . ':</span> ' . $res['temka'] . '<br/>';
                if (!empty($res['attach'])) {
                    echo '+ ' . $lng_pm['attachment'] . '<br/>';
                }
                if ($res['otvet'] == 0) {
                    echo $lng_pm['not_replyed'] . "<br/>";
                }
                echo '</div>';
                ++$i;
            }
            if ($total > 0) {
                echo '<div class="rmenu"><input type="submit" value="' . $lng_pm['delete_selected'] . '"/></div>';
            }
            echo '</form>';
            echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
            if ($total > $kmess) {
                echo '<p>' . functions::display_pagination('pradd.php?act=in&amp;', $start, $total, $kmess) . '</p>';
                echo '<p><form action="pradd.php?act=in" method="post"><input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
            }
            if ($total > 0) {
                echo "<a href='pradd.php?act=delread'>" . $lng_pm['delete_read'] . "</a><br/>";
                echo "<a href='pradd.php?act=delin'>" . $lng_pm['delete_all'] . "</a><br/>";
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
            echo  $lng_pm['read_deleted'] . "<br/>";
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
            echo $lng_pm['incoming_deleted'] . "<br/>";
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
                $text = functions::smileys($text, 1);
            echo "<p>" . $lng_pm['msg_from'] . " <a href='profile.php?user=" . $mass['id'] . "'>$massiv1[author]</a><br/>";
            $vrp = $massiv1['time'] + $set_user['sdvig'] * 3600;
            echo "(" . date("d.m.y H:i", $vrp) . ")</p><p><div class='b'>" . $lng_pm['subject'] . ": $massiv1[temka]<br/></div>" . $lng['text'] . ": $text</p>";
            if (!empty($massiv1['attach'])) {
                echo "<p>" . $lng_pm['attachment'] . ": <a href='?act=load&amp;id=" . $id . "'>$massiv1[attach]</a></p>";
            }
            echo "<hr /><p><a href='pradd.php?act=write&amp;adr=" . $mass['id'] . "&amp;id=" . $massiv1['id'] . "'>" . $lng['reply'] . "</a><br/><a href='pradd.php?act=delmess&amp;del=" . $massiv1['id'] . "'>" . $lng['delete'] . "</a></p>";
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
            echo $lng_pm['message_deleted'] . '<br/>';
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
            echo $lng_pm['sent_deleted'] . "<br/>";
            break;

        case 'out':
            ////////////////////////////////////////////////////////////
            // Список отправленных                                    //
            ////////////////////////////////////////////////////////////
            require_once('../incfiles/head.php');
            $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `author` = '$login' AND `type` = 'out'"), 0);
            $req = mysql_query("SELECT * FROM `privat` WHERE `author` = '$login' AND `type` = 'out' ORDER BY `id` DESC LIMIT $start,$kmess");
            echo '<div class="phdr"><b>' . $lng_pm['sent'] . '</b></div>';
            echo "<form action='pradd.php?act=delch' method='post'>";
            $i = 0;
            while ($res = mysql_fetch_assoc($req)) {
                if ($res['chit'] == "no") {
                    echo '<div class="gmenu">';
                } else {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                }
                echo '<input type="checkbox" name="delch[]" value="' . $res['id'] . '"/>' . $lng_pm['msg_for'] . ': <a href="pradd.php?id=' . $res['id'] . '&amp;act=readout">' . $res['user'] . '</a>';
                $vrp = $res['time'] + $set_user['sdvig'] * 3600;
                echo '&#160;<span class="gray">(' . date("d.m.y H:i", $vrp) . ')<br/>' . $lng_pm['subject'] . ':</span> ' . $res['temka'] . '<br/>';
                if (!empty($res['attach'])) {
                    echo "+ " . $lng_pm['attachment'] . "<br/>";
                }
                echo '</div>';
                ++$i;
            }
            if ($total > 0) {
                echo '<div class="rmenu"><input type="submit" value="' . $lng_pm['delete_selected'] . '"/></div>';
            }
            echo '</form>';
            echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
            if ($total > $kmess) {
                echo '<p>' . functions::display_pagination('pradd.php?act=out&amp;', $start, $total, $kmess) . '</p>';
                echo '<p><form action="pradd.php?act=out" method="post"><input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
            }
            if ($total > 0) {
                echo "<a href='pradd.php?act=delout'>" . $lng_pm['delete_all_sent'] . "</a><br/>";
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
            echo "<p>" . $lng_pm['msg_for'] . " <a href='profile.php?user=" . $mass['id'] . "'>$massiv1[user]</a><br/>";
            $vrp = $massiv1['time'] + $set_user['sdvig'] * 3600;
            echo "(" . date("d.m.y H:i", $vrp) . ")</p><p><div class='b'>" . $lng_pm['subject'] . ": $massiv1[temka]<br/></div>" . $lng['text'] . ": $text</p>";
            if (!empty($massiv1['attach'])) {
                echo "<p>" . $lng_pm['attachment'] . ": $massiv1[attach]</p>";
            }
            echo "<hr /><p><a href='pradd.php?act=delmess&amp;del=" . $massiv1['id'] . "'>" . $lng['delete'] . "</a></p>";
            break;
    }
    echo "<p><a href='profile.php?act=office'>" . $lng['personal'] . "</a><br/>";
    echo "<a href='pradd.php?act=write'>" . $lng['write'] . "</a></p>";
}

require_once('../incfiles/end.php');

?>
