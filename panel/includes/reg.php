<?php

defined('_IN_JOHNADM') or die('Error: restricted access');

// Проверяем права доступа
if ($rights < 6) {
    header('Location: http://johncms.com/?err');
    exit;
}

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['reg_approve'] . '</div>';

switch ($mod) {
    case 'approve':
        // Подтверждаем регистрацию выбранного пользователя
        if (!$id) {
            echo functions::display_error($lng['error_wrong_data']);
            require('../incfiles/end.php');
            exit;
        }

        $db->exec('UPDATE `users` SET `preg` = 1, `regadm` = ' . $db->quote($login) . ' WHERE `id` = ' . $id);
        echo '<div class="menu"><p>' . $lng['reg_approved'] . '<br /><a href="index.php?act=reg">' . $lng['continue'] . '</a></p></div>';
        break;

    case 'massapprove':
        // Подтверждение всех регистраций
        $db->exec('UPDATE `users` SET `preg` = 1, `regadm` = ' . $db->exec($login) . ' WHERE `preg` = 0');
        echo '<div class="menu"><p>' . $lng['reg_approved'] . '<br /><a href="index.php?act=reg">' . $lng['continue'] . '</a></p></div>';
        break;

    case 'del':
        // Удаляем регистрацию выбранного пользователя
        if (!$id) {
            echo functions::display_error($lng['error_wrong_data']);
            require('../incfiles/end.php');
            exit;
        }

        $req = $db->query("SELECT `id` FROM `users` WHERE `id` = '$id' AND `preg` = '0'");

        if ($req->rowCount()) {
            $db->exec("DELETE FROM `users` WHERE `id` = '$id'");
            $db->exec("DELETE FROM `cms_users_iphistory` WHERE `user_id` = '$id' LIMIT 1");
        }

        echo '<div class="menu"><p>' . $lng['user_deleted'] . '<br /><a href="index.php?act=reg">' . $lng['continue'] . '</a></p></div>';
        break;

    case 'massdel':
        $db->exec("DELETE FROM `users` WHERE `preg` = '0'");
        $db->query("OPTIMIZE TABLE `cms_users_iphistory` , `users`");
        echo '<div class="menu"><p>' . $lng['reg_deleted_all'] . '<br /><a href="index.php?act=reg">' . $lng['continue'] . '</a></p></div>';
        break;

    case 'delip':
        // Удаляем все регистрации с заданным адресом IP
        $ip = isset($_GET['ip']) ? intval($_GET['ip']) : false;

        if ($ip) {
            $req = $db->query("SELECT `id` FROM `users` WHERE `preg` = '0' AND `ip` = '$ip'");

            while ($res = $req->fetch()) {
                $db->exec("DELETE FROM `cms_users_iphistory` WHERE `user_id` = '" . $res['id'] . "'");
            }

            $db->exec("DELETE FROM `users` WHERE `preg` = '0' AND `ip` = '$ip'");
            $db->query("OPTIMIZE TABLE `cms_users_iphistory` , `users`");
            echo '<div class="menu"><p>' . $lng['reg_del_ip_done'] . '<br />' .
                '<a href="index.php?act=reg">' . $lng['continue'] . '</a></p></div>';
        } else {
            echo functions::display_error($lng['error_wrong_data']);
            require('../incfiles/end.php');
            exit;
        }
        break;

    default:
        // Выводим список пользователей, ожидающих подтверждения регистрации
        $total = $db->query("SELECT COUNT(*) FROM `users` WHERE `preg` = '0'")->fetchColumn();

        if ($total > $kmess) {
            echo '<div class="topmenu">' . functions::display_pagination('index.php?act=reg&amp;', $start, $total, $kmess) . '</div>';
        }

        if ($total) {
            $req = $db->query("SELECT * FROM `users` WHERE `preg` = '0' ORDER BY `id` DESC LIMIT $start,$kmess");
            $i = 0;

            while ($res = $req->fetch()) {
                $link = [
                    '<a href="index.php?act=reg&amp;mod=approve&amp;id=' . $res['id'] . '">' . $lng['approve'] . '</a>',
                    '<a href="index.php?act=reg&amp;mod=del&amp;id=' . $res['id'] . '">' . $lng['delete'] . '</a>',
                    '<a href="index.php?act=reg&amp;mod=delip&amp;ip=' . $res['ip'] . '">' . $lng['reg_del_ip'] . '</a>',
                ];
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                echo functions::display_user($res, [
                    'header' => '<b>ID:' . $res['id'] . '</b>',
                    'sub'    => functions::display_menu($link),
                ]);
                echo '</div>';
                ++$i;
            }
        } else {
            echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
        }

        echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';

        if ($total > $kmess) {
            echo '<div class="topmenu">' . functions::display_pagination('index.php?act=reg&amp;', $start, $total, $kmess) . '</div>' .
                '<p><form action="index.php?act=reg" method="post">' .
                '<input type="text" name="page" size="2"/>' .
                '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
                '</form></p>';
        }

        echo '<p>';

        if ($total) {
            echo '<a href="index.php?act=reg&amp;mod=massapprove">' . $lng['reg_approve_all'] . '</a><br /><a href="index.php?act=reg&amp;mod=massdel">' . $lng['reg_del_all'] . '</a><br />';
        }

        echo '<a href="index.php">' . $lng['admin_panel'] . '</a></p>';
}
