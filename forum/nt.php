<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

if (!$id || !$user_id || $ban['1'] || $ban['11']) {
    header("Location: index.php");
    exit;
}
// Проверка на спам
$old = ($rights > 0) ? 10 : 30;
if ($datauser['lastpost'] > ($realtime - $old)) {
    require_once ("../incfiles/head.php");
    echo '<div class="rmenu"><p>АНТИФЛУД!<br />Вы не можете так часто писать, порог ' . $old . ' секунд<br/><a href="?id=' . $id . '&amp;start=' . $start . '">Назад</a></p></div>';
    require_once ("../incfiles/end.php");
    exit;
}

$type = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id'");
$type1 = mysql_fetch_array($type);
$tip = $type1['type'];
if ($tip != "r") {
    require_once ("../incfiles/head.php");
    echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
if (isset ($_POST['submit'])) {
    $error = false;
    if (empty ($_POST['th']))
        $error = '<div>Вы не ввели название темы</div>';
    if (empty ($_POST['msg']))
        $error .= '<div>Вы не ввели сообщение</div>';
    if (!$error) {
        $th = mb_substr($th, 0, 100);
        $th = check($_POST['th']);
        $msg = trim($_POST['msg']);
        if ($_POST['msgtrans'] == 1) {
            $th = trans($th);
            $msg = trans($msg);
        }
        // Прверяем, есть ли уже такая тема в текущем разделе?
        if (mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't' AND `refid` = '$id' AND `text` = '$th'"), 0) > 0)
            $error = 'Тема с таким названием уже есть в этом разделе';
        // Проверяем, не повторяется ли сообщение?
        $req = mysql_query("SELECT * FROM `forum` WHERE `user_id` = '$user_id' AND `type` = 'm' ORDER BY `time` DESC");
        if (mysql_num_rows($req) > 0) {
            $res = mysql_fetch_array($req);
            if ($msg == $res['text'])
                $error = 'Такое сообщение уже было';
        }
    }
    if (!$error) {
        // Добавляем тему
        mysql_query("INSERT INTO `forum` SET
		`refid` = '$id',
		`type` = 't',
		`time` = '$realtime',
		`user_id` = '$user_id',
		`from` = '$login',
		`text` = '$th'");
        $rid = mysql_insert_id();
        // Добавляем текст поста
        mysql_query("INSERT INTO `forum` SET
		`refid` = '$rid',
		`type` = 'm',
		`time` = '$realtime',
		`user_id` = '$user_id',
		`from` = '$login',
		`ip` = '$ipp',
		`soft` = '" . mysql_real_escape_string($agn) . "',
		`text` = '" .
        mysql_real_escape_string($msg) . "'");
        $postid = mysql_insert_id();
        // Записываем счетчик постов юзера
        $fpst = $datauser['postforum'] + 1;
        mysql_query("UPDATE `users` SET  `postforum` = '$fpst', `lastpost` = '$realtime' WHERE `id` = '$user_id'");
        // Ставим метку о прочтении
        mysql_query("INSERT INTO `cms_forum_rdm` SET  `topic_id`='$rid', `user_id`='$user_id', `time`='$realtime'");
        if ($_POST['addfiles'] == 1)
            header("Location: index.php?id=$postid&act=addfile");
        else
            header("Location: index.php?id=$rid");
    }
    else {
        // Выводим сообщение об ошибке
        require_once ('../incfiles/head.php');
        echo '<div class="rmenu"><p>ОШИБКА!<br />' . $error . '<br /><a href="index.php?act=nt&amp;id=' . $id . '">Повторить</a></p></div>';
        require_once ('../incfiles/end.php');
        exit;
    }
}
else {
    require_once ('../incfiles/head.php');
    if ($datauser['postforum'] == 0) {
        if (!isset ($_GET['yes'])) {
            include ('../pages/forum.txt');
            echo "<a href='index.php?act=nt&amp;id=" . $id . "&amp;yes'>Согласен</a> | <a href='index.php?id=" . $id . "'>Не согласен</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
    }
    echo '<div class="phdr">Добавление темы</div><div class="menu">Раздел: ' . $type1['text'] . '</div>';
    echo '<form action="index.php?act=nt&amp;id=' . $id . '" method="post">';
    echo '<div class="gmenu"><p>Название(max. 100):<br/><input type="text" size="20" maxlength="100" name="th"/><br/>';
    echo 'Сообщение:<br/><textarea cols="' . $set_forum['farea_w'] . '" rows="' . $set_forum['farea_h'] . '" name="msg"></textarea><br />';
    echo '<input type="checkbox" name="addfiles" value="1" /> Добавить файл';
    if ($set_user['translit'])
        echo '<br /><input type="checkbox" name="msgtrans" value="1" /> Транслит сообщения';
    echo '</p><p><input type="submit" name="submit" value="Отправить"/></p></div></form>';
    echo '<div class="phdr"><a href="index.php?act=trans">Транслит</a> | <a href="../str/smile.php">Смайлы</a></div>';
    echo '<p><a href="?id=' . $id . '">Назад</a></p>';
}

?>