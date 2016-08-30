<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');
// TODO: закончить перевод
if ($id > 0 && $user_id && !$ban['1'] && !$ban['10'] && ($set['mod_down_comm'] || $rights < 7)) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    $file = $db->query("SELECT * FROM `download` WHERE `type` = 'file' AND `id` = '" . $id . "'");

    if (!$file->rowCount()) {
        require_once("../incfiles/head.php");
        echo "Не выбран файл<br><a href='?'>К категориям</a><br>";
        require_once('../incfiles/end.php');
        exit;
    }

    if (isset ($_POST['submit'])) {
        // Проверка на флуд
        $flood = functions::antiflood();

        if ($flood) {
            require_once('../incfiles/head.php');
            echo functions::display_error('Вы не можете так часто добавлять сообщения<br />Пожалуйста, подождите ' . $flood . ' сек.', '<a href="index.php?act=komm&amp;id=' . $id . '">Назад</a>');
            require_once('../incfiles/end.php');
            exit;
        }

        if ($_POST['msg'] == "") {
            require_once("../incfiles/head.php");
            echo "Вы не ввели сообщение!<br><a href='?act=komm&amp;id=" . $id . "'>К комментариям</a><br>";
            require_once('../incfiles/end.php');
            exit;
        }

        $msg = trim($_POST['msg']);
        $msg = mb_substr($msg, 0, 500);
        $agn = strtok($agn, ' ');
        $db->query("insert into `download` values(0,'$id','','" . time() . "','','komm'," . $db->quote($login) . ",'" . long2ip($ip) . "','" . $agn . "'," . $db->quote($msg) . ",'')");
        $fpst = $datauser['komm'] + 1;
        $db->exec("UPDATE `users` SET
		`komm`='" . $fpst . "',
		`lastpost` = '" . time() . "'
		WHERE `id`='" . $user_id . "'");
        header("Location: index.php?act=komm&id=$id");
    } else {
        require_once("../incfiles/head.php");
        echo "<form action='?act=addkomm&amp;id=" . $id .
            "' method='post'>
" . $lng['message'] . " (max. 500)<br>
<textarea rows='3' name='msg' ></textarea><br><br>
<input type='submit' name='submit' value='" . $lng['add'] . "' />
  </form>";
    }
} else {
    require_once("../incfiles/head.php");
    echo "Вы не авторизованы!<br>";
}

echo '<br><a href="?act=komm&amp;id=' . $id . '">' . $lng['comments'] . '</a><br><a href="?act=view&amp;file=' . $id . '">' . $lng_dl['file'] . '</a><br>';
