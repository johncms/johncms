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

if ($user_id && !$ban['1'] && !$ban['10'] && ($set['mod_lib_comm'] || $rights >= 7)) {
    if (!$id) {
        echo "Не выбрана статья<br/><a href='?'>К категориям</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $req = mysql_query("SELECT `name` FROM `lib` WHERE `type` = 'bk' AND `id` = '" . $id . "' LIMIT 1");
    if (mysql_num_rows($req) != 1) {
        // если статья не существует, останавливаем скрипт
        echo '<p>Не выбрана статья<br/><a href="index.php">К категориям</a></p>';
        require_once ('../incfiles/end.php');
        exit;
    }
    // Проверка на флуд
    $flood = antiflood();
    if ($flood){
        require_once ('../incfiles/head.php');
        echo display_error('Вы не можете так часто добавлять сообщения<br />Пожалуйста, подождите ' . $flood . ' сек.', '<a href="?act=komm&amp;id=' . $id . '">Назад</a>');
        require_once ('../incfiles/end.php');
        exit;
    }
    if (isset ($_POST['submit'])) {
        if ($_POST['msg'] == "") {
            echo "Вы не ввели сообщение!<br/><a href='index.php?act=komm&amp;id=" . $id . "'>К комментариям</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        $msg = check(trim($_POST['msg']));
        if ($_POST['msgtrans'] == 1) {
            $msg = trans($msg);
        }
        $msg = mb_substr($msg, 0, 500);
        $agn = strtok($agn, ' ');
        mysql_query("INSERT INTO `lib` SET
        `refid` = '" . $id . "',
        `time` = '" . $realtime . "',
        `type` = 'komm',
        `avtor` = '" . $login . "',
        `count` = '" . $user_id . "',
        `text` = '" . $msg
        . "',
        `ip` = '" . $ipl . "',
        `soft` = '" . mysql_real_escape_string($agn) . "'");
        $fpst = $datauser['komm'] + 1;
        mysql_query("UPDATE `users` SET
		`komm`='" . $fpst . "',
		`lastpost` = '" . $realtime . "'
		WHERE `id`='" . $user_id . "'");
        echo '<p>Комментарий успешно добавлен<br />';
    }
    else {
        echo "<p>Напишите комментарий<br/><br/><form action='?act=addkomm&amp;id=" . $id .
        "' method='post'>
Cообщение(max. 500)<br/>
<textarea rows='3' name='msg'></textarea><br/><br/>
<input type='checkbox' name='msgtrans' value='1' /> Транслит<br/>
<input type='submit' name='submit' value='добавить' />
  </form><br/>";
        echo "<a href='index.php?act=trans'>Транслит</a><br /><a href='../str/smile.php'>Смайлы</a><br/>";
    }
    echo '<a href="?act=komm&amp;id=' . $id . '">К комментариям</a></p>';
}
else {
    echo "<p>Ошибка</p>";
}

?>