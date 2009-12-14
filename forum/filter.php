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

require_once ('../incfiles/head.php');
if (!$id) {
    echo '<div class="rmenu">ОШИБКА!<br /><a href="index.php">В форум</a></div>';
    require_once ('../incfiles/end.php');
    exit;
}

$do
    = isset ($_GET['do']) ? trim($_GET['do']) : '';
switch ($do
        ) {
        case 'unset' :
            unset ($_SESSION['fsort_id']);
            unset ($_SESSION['fsort_users']);
            header("Location: index.php?id=$id");
            break;

        case 'set' :
        $users = isset ($_POST['users']) ? $_POST['users'] : '';
        if (empty ($_POST['users'])) {
            echo '<div class="rmenu"><p>Вы не выбрали ни одного автора<br /><a href="index.php?act=filter&amp;id=' . $id . '&amp;start=' . $start . '">Назад</a></p></div>';
            require_once ('../incfiles/end.php');
            exit;
        }
        $array = array();
        foreach ($users as $val) {
            $array [] = intval($val);
        }
        $_SESSION['fsort_id'] = $id;
        $_SESSION['fsort_users'] = serialize($array);
        header("Location: index.php?id=$id");
        break;

    default :
        ////////////////////////////////////////////////////////////
        // Отображаем сгруппированный список юзеров               //
        ////////////////////////////////////////////////////////////
        $req = mysql_query("SELECT *, COUNT(`from`) AS `count` FROM `forum` WHERE `refid` = '$id' GROUP BY `from` ORDER BY `from`");
        $total = mysql_num_rows($req);
        if ($total > 0) {
            echo '<form action="index.php?act=filter&amp;id=' . $id . '&amp;start=' . $start . '&amp;do=set" method="post">';
            echo '<div class="phdr">Фильтрация постов</div>';
            while ($res = mysql_fetch_array($req)) {
                echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
                echo '<input type="checkbox" name="users[]" value="' . $res['user_id'] . '"/>&nbsp;';
                echo '<a href="../str/anketa.php?id=' . $res['user_id'] . '">' . $res['from'] . '</a> [' . $res['count'] . ']</div>';
                ++$i;
            }
            echo
            '<div class="phdr"><small>Отметьте нужных авторов и нажмите кнопку &quot;Фильтровать&quot;<br />Фильтрация позволит отображать посты только от выбранных авторов.</small></div>';
            echo '<input type="submit" value="Фильтровать" name="submit" /></form>';
        }
        else {
            echo 'Ошибка';
        }
}

echo '<p><a href="index.php?id=' . $id . '&amp;start=' . $start . '">Вернуться в тему</a></p>';
require_once ('../incfiles/end.php');

?>