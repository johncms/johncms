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

if ($rights == 3 || $rights >= 6) {
    if (empty ($_GET['id'])) {
        require_once ("../incfiles/head.php");
        echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    $id = intval(check($_GET['id']));
    $typ = mysql_query("select * from `forum` where id='" . $id . "';");
    $ms = mysql_fetch_array($typ);
    if ($ms[type] != "t") {
        require_once ("../incfiles/head.php");
        echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    if (isset ($_POST['submit'])) {
        if (empty ($_POST['razd'])) {
            require_once ("../incfiles/head.php");
            echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
            require_once ("../incfiles/end.php");
            exit;
        }
        $razd = intval(check($_POST['razd']));
        $typ1 = mysql_query("select * from `forum` where id='" . $razd . "';");
        $ms1 = mysql_fetch_array($typ1);
        if ($ms1[type] != "r") {
            require_once ("../incfiles/head.php");
            echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
            require_once ("../incfiles/end.php");
            exit;
        }
        mysql_query("update `forum` set  refid='" . $razd . "' where id='" . $id . "';");
        header("Location: index.php?id=$id");
    }
    else {
        require_once ("../incfiles/head.php");
        if (empty ($_GET['other'])) {
            $rz = mysql_query("select * from `forum` where id='" . $ms['refid'] . "';");
            $rz1 = mysql_fetch_assoc($rz);
            $other = $rz1['refid'];
        }
        else {
            $other = intval(check($_GET['other']));
        }
        $fr = mysql_query("select * from `forum` where id='" . $other . "';");
        $fr1 = mysql_fetch_assoc($fr);
        echo '<div class="phdr"><b>Перенос темы</b></div>';
        echo '<div class="bmenu">Категория: ' . $fr1['text'] . '</div>';
        echo '<form action="index.php?act=per&amp;id=' . $id . '" method="post"><div class="gmenu"><p>Выберите раздел:<br/>';
        echo "<select name='razd'>";
        $raz = mysql_query("SELECT * FROM `forum` WHERE `refid` = '$other' AND `type` = 'r' AND `id` != '" . $ms['refid'] . "' ORDER BY `realid` ASC");
        while ($raz1 = mysql_fetch_assoc($raz)) {
            echo '<option value="' . $raz1['id'] . '">' . $raz1['text'] . '</option>';
        }
        echo '</select><br/><input type="submit" name="submit" value="Перенести"/></p></div></form>';
        echo '<div class="phdr">Другие категории</div>';
        $frm = mysql_query("SELECT * FROM `forum` WHERE `type` = 'f' AND `id` != '$other' ORDER BY `realid` ASC");
        while ($frm1 = mysql_fetch_assoc($frm)) {
            echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
            echo '<a href="index.php?act=per&amp;id=' . $id . '&amp;other=' . $frm1['id'] . '">' . $frm1['text'] . '</a></div>';
            ++$i;
        }
        echo '<div class="phdr"><a href="index.php">В форум</a></div>';
    }
}

?>