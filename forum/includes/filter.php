<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');
require('../incfiles/head.php');

if (!$id) {
    echo functions::display_error(_t('Wrong data'), '<a href="index.php">' . $lng['to_forum'] . '</a>');
    require('../incfiles/end.php');
    exit;
}

switch ($do) {
    case 'unset':
        // Удаляем фильтр
        unset($_SESSION['fsort_id']);
        unset($_SESSION['fsort_users']);
        header("Location: index.php?id=$id");
        break;

    case 'set':
        // Устанавливаем фильтр по авторам
        $users = isset($_POST['users']) ? $_POST['users'] : '';

        if (empty($_POST['users'])) {
            echo '<div class="rmenu"><p>' . $lng_forum['error_author_select'] . '<br /><a href="index.php?act=filter&amp;id=' . $id . '&amp;start=' . $start . '">' . _t('Back') . '</a></p></div>';
            require('../incfiles/end.php');
            exit;
        }

        $array = [];

        foreach ($users as $val) {
            $array[] = intval($val);
        }

        $_SESSION['fsort_id'] = $id;
        $_SESSION['fsort_users'] = serialize($array);
        header("Location: index.php?id=$id");
        break;

    default :
        /** @var PDO $db */
        $db = App::getContainer()->get(PDO::class);

        // Показываем список авторов темы, с возможностью выбора
        $req = $db->query("SELECT *, COUNT(`from`) AS `count` FROM `forum` WHERE `refid` = '$id' GROUP BY `from` ORDER BY `from`");
        $total = $req->rowCount();

        if ($total) {
            echo '<div class="phdr"><a href="index.php?id=' . $id . '&amp;start=' . $start . '"><b>' . _t('Forum') . '</b></a> | ' . $lng_forum['filter_on_author'] . '</div>' .
                '<form action="index.php?act=filter&amp;id=' . $id . '&amp;start=' . $start . '&amp;do=set" method="post">';
            $i = 0;

            while ($res = $req->fetch()) {
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                echo '<input type="checkbox" name="users[]" value="' . $res['user_id'] . '"/>&#160;' .
                    '<a href="../profile/?user=' . $res['user_id'] . '">' . $res['from'] . '</a> [' . $res['count'] . ']</div>';
                ++$i;
            }

            echo '<div class="gmenu"><input type="submit" value="' . $lng_forum['filter_to'] . '" name="submit" /></div>' .
                '<div class="phdr"><small>' . $lng_forum['filter_on_author_help'] . '</small></div>' .
                '</form>';
        } else {
            echo functions::display_error(_t('Wrong data'));
        }
}

echo '<p><a href="index.php?id=' . $id . '&amp;start=' . $start . '">' . $lng_forum['return_to_topic'] . '</a></p>';
