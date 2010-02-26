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

defined('_IN_JOHNADM') or die('Error: restricted access');

if ($rights < 7)
    die('Error: restricted access');

echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Управление Чатом</div>';
switch ($mod) {
    case 'del' :
        if (empty ($_GET['id'])) {
            echo "Ошибка!<br/><a href='chat.php?'>В управление чатом</a><br/>";
            require_once ("../incfiles/end.php");
            exit;
        }
        $typ = mysql_query("select * from `chat` where id='" . $id . "';");
        $ms = mysql_fetch_array($typ);
        if ($ms['type'] != "r") {
            echo "Ошибка!<br/><a href='chat.php?'>В управление чатом</a><br/>";
            require_once ("../incfiles/end.php");
            exit;
        }
        switch ($ms['type']) {
            case 'r' :
                if (isset ($_GET['yes'])) {
                    $mes = mysql_query("select * from `chat` where refid='" . $id . "';");
                    while ($mes1 = mysql_fetch_array($mes)) {
                        mysql_query("delete from `chat` where `id`='" . $mes1['id'] . "';");
                    }
                    mysql_query("delete from `chat` where `id`='" . $id . "';");
                    header("Location: index.php?act=mod_chat");
                }
                else {
                    echo "Вы уверены,что хотите удалить комнату $ms[text]?<br/><a href='index.php?act=mod_chat&amp;mod=del&amp;id=" . $id . "&amp;yes'>Да</a> | <a href='index.php?act=mod_chat'>Нет</a><br/>";
                }
                break;
            default :
                echo "Ошибка!<br/><a href='index.php?act=mod_chat'>В управление чатом</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
                break;
        }
        break;

    case 'add' :
        if (isset ($_POST['submit'])) {
            if ((empty ($_POST['tr'])) && (empty ($_POST['nr']))) {
                echo "Вы не ввели имя комнаты!<br/><a href='chat.php?act=crroom'>Повторить</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $nr = check($_POST['nr']);
            $tr = check($_POST['tr']);
            if ($tr == "vik") {
                $nr = "Викторина";
            }
            if ($tr == "in") {
                $nr = "Интим";
            }
            $q = mysql_query("select * from `chat` where type='r' order by realid desc;");
            $q1 = mysql_num_rows($q);
            if ($q1 == 0) {
                $rid = 1;
            }
            else {
                while ($arr = mysql_fetch_array($q)) {
                    $arr1[] = $arr['realid'];
                }
                $rid = $arr1[0] + 1;
            }
            mysql_query("INSERT INTO `chat` SET
            `realid` = '$rid',
            `type` = 'r',
            `dpar` = '$tr',
            `text` = '$nr'");
            header("Location: index.php?act=mod_chat");
        }
        else {
            echo "Добавление комнаты:<br/><form action='index.php?act=mod_chat&amp;mod=add' method='post'>Тип комнаты<br/><select name='tr'><option value=''>простая</option>";
            $v = mysql_query("select * from `chat` where type='r' and dpar='vik';");
            $v1 = mysql_num_rows($v);
            $a = mysql_query("select * from `chat` where type='r' and dpar='in';");
            $a1 = mysql_num_rows($a);
            if ($v1 == 0) {
                echo "<option value='vik'>викторина</option>";
            }
            if ($a1 == 0) {
                echo "<option value='in'>интим</option>";
            }
            echo "</select><br/>Название(если простая):<br/><input type='text' name='nr'/><br/><input type='submit' name='submit' value='Ok!'/><br/></form>";
            echo "<a href='index.php?act=mod_chat'>В управление чатом</a><br/>";
        }
        break;

    case 'edit' :
        if (!$id) {
            echo "Ошибка!<br/><a href='index.php?act=mod_chat'>В управление чатом</a><br/>";
            require_once ("../incfiles/end.php");
            exit;
        }
        $typ = mysql_query("select * from `chat` where id='" . $id . "';");
        $ms = mysql_fetch_array($typ);
        if ($ms['type'] != "r") {
            echo "Ошибка!<br/><a href='chat.php?'>В управление чатом</a><br/>";
            require_once ("../incfiles/end.php");
            exit;
        }
        if (isset ($_POST['submit'])) {
            if ((empty ($_POST['tr'])) && ((empty ($_POST['nr'])) || $_POST['nr'] == "Викторина" || $_POST['nr'] == "Интим")) {
                echo "Вы не ввели новое название!<br/><a href='chat.php?act=edit&amp;id=" . $id . "'>Повторить</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $nr = check(trim($_POST['nr']));
            $tr = check(trim($_POST['tr']));
            if ($tr == "vik") {
                $nr = "Викторина";
            }
            if ($tr == "in") {
                $nr = "Интим";
            }
            mysql_query("update `chat` set  dpar='" . $tr . "',text='" . $nr . "' where id='" . $id . "';");
            header("Location: index.php?act=mod_chat");
        }
        else {
            echo "Изменить комнату<br/><form action='index.php?act=mod_chat&amp;mod=edit&amp;id=" . $id . "' method='post'>Тип комнаты<br/><select name='tr'>";
            $v = mysql_query("select * from `chat` where type='r' and dpar='vik';");
            $v1 = mysql_num_rows($v);
            $a = mysql_query("select * from `chat` where type='r' and dpar='in';");
            $a1 = mysql_num_rows($a);
            if (empty ($ms['dpar'])) {
                echo "<option value=''>простая</option>";
                if ($v1 == 0) {
                    echo "<option value='vik'>викторина</option>";
                }
                if ($a1 == 0) {
                    echo "<option value='in'>интим</option>";
                }
            }

            if ($ms['dpar'] == "vik") {
                echo "<option value='vik'>викторина</option><option value=''>простая</option>";
                if ($a1 == 0) {
                    echo "<option value='in'>интим</option>";
                }
            }

            if ($ms['dpar'] == "in") {
                echo "<option value='in'>интим</option><option value=''>простая</option>";
                if ($v1 == 0) {
                    echo "<option value='vik'>викторина</option>";
                }
            }
            echo "</select><br/>Изменить название(если простая):<br/><input type='text' name='nr' value='" . $ms[text] . "'/><br/><input type='submit' name='submit' value='Ok!'/><br/></form>";
        }
        echo "<a href='index.php?act=mod_chat'>В управление чатом</a><br/>";
        break;

    case 'up' :
        ////////////////////////////////////////////////////////////
        // Перемещение комнаты на одну позицию вверх              //
        ////////////////////////////////////////////////////////////
        if ($id) {
            $req = mysql_query("SELECT `realid` FROM `chat` WHERE `type` = 'r' AND `id` = '$id' LIMIT 1");
            if (mysql_num_rows($req)) {
                $res = mysql_fetch_assoc($req);
                $sort = $res['realid'];
                $req = mysql_query("SELECT * FROM `chat` WHERE `type` = 'r' AND `realid` < '$sort' ORDER BY `realid` DESC LIMIT 1");
                if (mysql_num_rows($req)) {
                    $res = mysql_fetch_assoc($req);
                    $id2 = $res['id'];
                    $sort2 = $res['realid'];
                    mysql_query("UPDATE `chat` SET `realid` = '$sort2' WHERE `id` = '$id'");
                    mysql_query("UPDATE `chat` SET `realid` = '$sort' WHERE `id` = '$id2'");
                }
            }
        }
        header('Location: index.php?act=mod_chat');
        break;

    case 'down' :
        ////////////////////////////////////////////////////////////
        // Перемещение комнаты на одну позицию вниз               //
        ////////////////////////////////////////////////////////////
        if ($id) {
            $req = mysql_query("SELECT `realid` FROM `chat` WHERE `type` = 'r' AND `id` = '$id' LIMIT 1");
            if (mysql_num_rows($req)) {
                $res = mysql_fetch_assoc($req);
                $sort = $res['realid'];
                $req = mysql_query("SELECT * FROM `chat` WHERE `type` = 'r' AND `realid` > '$sort' ORDER BY `realid` ASC LIMIT 1");
                if (mysql_num_rows($req)) {
                    $res = mysql_fetch_assoc($req);
                    $id2 = $res['id'];
                    $sort2 = $res['realid'];
                    mysql_query("UPDATE `chat` SET `realid` = '$sort2' WHERE `id` = '$id'");
                    mysql_query("UPDATE `chat` SET `realid` = '$sort' WHERE `id` = '$id2'");
                }
            }
        }
        header('Location: index.php?act=mod_chat');
        break;

    default :
        ////////////////////////////////////////////////////////////
        // Список комнат Чата                                     //
        ////////////////////////////////////////////////////////////
        $req = mysql_query("SELECT * FROM `chat` WHERE `type` = 'r' ORDER BY `realid`");
        while ($res = mysql_fetch_assoc($req)) {
            echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
            echo '<b>' . $res[text] . '</b><br />';
            echo '<div class="sub"><a href="index.php?act=mod_chat&amp;mod=up&amp;id=' . $res['id'] . '">Вверх</a> | ';
            echo '<a href="index.php?act=mod_chat&amp;mod=down&amp;id=' . $res['id'] . '">Вниз</a> | ';
            echo '<a href="index.php?act=mod_chat&amp;mod=edit&amp;id=' . $res['id'] . '">Изм.</a> | ';
            echo '<a href="index.php?act=mod_chat&amp;mod=del&amp;id=' . $res['id'] . '">Удалить</a></div></div>';
            ++$i;
        }
        echo '<div class="gmenu"><form action="index.php?act=mod_chat&amp;mod=add" method="post"><input type="submit" value="Добавить комнату" /></form></div>';
        echo '<div class="phdr"><a href="../chat/index.php">В чат</a></div>';
}

echo '<p>' . ($mod ? '<a href="index.php?act=mod_chat">Управление Чатом</a><br />' : '') . '<a href="index.php">Админ панель</a></p>';

?>