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

define('_IN_JOHNCMS', 1);

$textl = 'Новости ресурса';
$headmod = "news";
require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");

$do
    = isset ($_GET['do']) ? $_GET['do'] : '';
switch ($do
        ) {
        case 'add' :
            ////////////////////////////////////////////////////////////
            // Добавление новости                                     //
            ////////////////////////////////////////////////////////////
            if ($rights >= 6) {
                echo '<div class="phdr">Добавить новость</div>';
                $old = 20;
                if ($datauser['lastpost'] > ($realtime - $old)) {
                    echo '<p><b>Антифлуд!</b><br />Вы не можете так часто писать<br/>Порог ' . $old . ' секунд<br/><br/><a href="news.php">Назад</a></p>';
                    require_once ("../incfiles/end.php");
                    exit;
                }
                if (isset ($_POST['submit'])) {
                    if (empty ($_POST['name'])) {
                        echo "Вы не ввели заголовок<br/><a href='news.php?act=new'>Повторить</a><br/>";
                        require_once ("../incfiles/end.php");
                        exit;
                    }
                    if (empty ($_POST['text'])) {
                        echo "Вы не ввели текст<br/><a href='news.php?act=new'>Повторить</a><br/>";
                        require_once ("../incfiles/end.php");
                        exit;
                    }
                    $name = check($_POST['name']);
                    $text = trim($_POST['text']);
                    if (!empty ($_POST['pf']) && ($_POST['pf'] != '0')) {
                        $pf = intval($_POST['pf']);
                        $rz = $_POST['rz'];
                        $pr = mysql_query("SELECT * FROM `forum` WHERE `refid` = '$pf' AND `type` = 'r'");
                        while ($pr1 = mysql_fetch_array($pr)) {
                            $arr[] = $pr1['id'];
                        }
                        foreach ($rz as $v) {
                            if (in_array($v, $arr)) {
                                mysql_query(
                                "INSERT INTO `forum` SET
                            `refid` = '$v',
                            `type` = 't',
                            `time` = '$realtime',
                            `user_id` = '$user_id',
                            `from` = '$login',
                            `text` = '$name'"
                                );
                                $rid = mysql_insert_id();
                                mysql_query(
                                "INSERT INTO `forum` SET
                            `refid` = '$rid',
                            `type` = 'm',
                            `time` = '$realtime',
                            `user_id` = '$user_id',
                            `from` = '$login',
                            `ip` = '$ipp',
                            `soft` = '"
                                . mysql_real_escape_string($agn) . "',
                            `text` = '" . mysql_real_escape_string($text) . "'");
                            }
                        }
                    }
                    mysql_query("insert into `news` values(0,'$realtime','$login','$name','" . mysql_real_escape_string($text) . "','$rid')");
                    mysql_query("UPDATE `users` SET `lastpost` = '$realtime' WHERE `id` = '$user_id'");
                    echo "Новость добавлена.<p><a href='news.php'>Продолжить</a></p>";
                }
                else {
                    echo '<form action="news.php?do=add" method="post">';
                    echo '<div class="menu"><u>Заголовок</u><br/><input type="text" name="name"/></div>';
                    echo '<div class="menu"><u>Текст</u><br/><textarea rows="4" name="text"></textarea></div>';
                    echo '<div class="menu"><u>Раздел форума для обсуждения новости</u><br/>';
                    $fr = mysql_query("SELECT * FROM `forum` WHERE `type` = 'f'");
                    echo '<input type="radio" name="pf" value="0" checked="checked" />Не обсуждать<br />';
                    while ($fr1 = mysql_fetch_array($fr)) {
                        echo "<input type='radio' name='pf' value='" . $fr1['id'] . "'/>$fr1[text]<select name='rz[]'>";
                        $pr = mysql_query("select * from `forum` where type='r' and refid= '" . $fr1['id'] . "'");
                        while ($pr1 = mysql_fetch_array($pr)) {
                            echo '<option value="' . $pr1['id'] . '">' . $pr1['text'] . '</option>';
                        }
                        echo '</select><br/>';
                    }
                    echo '</div><div class="bmenu"><input type="submit" name="submit" value="Ok!"/></div></form><p><a href="news.php">К новостям</a></p>';
                }
            }
            else {
                header("location: news.php");
            }
            break;

        case 'edit' :
        ////////////////////////////////////////////////////////////
        // Редактирование новости                                 //
        ////////////////////////////////////////////////////////////
        if ($rights >= 6) {
            echo '<div class="phdr">Редактирование новости</div>';
            if (empty ($_GET['id'])) {
                echo "Ошибка!<br/><a href='news.php'>К новостям</a><br>";
                require_once ("../incfiles/end.php");
                exit;
            }
            if (isset ($_POST['submit'])) {
                if (empty ($_POST['name'])) {
                    echo "Вы не ввели заголовок<br/><a href='news.php?act=edit&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                if (empty ($_POST['text'])) {
                    echo "Вы не ввели текст<br/><a href='news.php?act=edit&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                $name = check($_POST['name']);
                $text = mysql_real_escape_string(trim($_POST['text']));
                mysql_query("UPDATE `news` SET
			`name` = '" . $name . "',
			`text` = '" . $text . "'
			WHERE `id` = '" . $id . "';");
                echo '<p>Новость изменена.<br /><a href="news.php">Продолжить</a></p>';
            }
            else {
                $req = mysql_query("SELECT * FROM `news` WHERE `id` = '" . $id . "'");
                $res = mysql_fetch_array($req);
                echo '<form action="news.php?do=edit&amp;id=' . $id . '" method="post">Заголовок:<br/><input type="text" name="name" value="' . $res['name'] . '"/><br/>Текст:<br/><textarea cols="30" rows="5" name="text">' .
                htmlentities($res['text'], ENT_QUOTES, 'UTF-8') . '</textarea><br/><input type="submit" name="submit" value="Ok!"/></form><p><a href="news.php">К новостям</a></p>';
            }
        }
        else {
            header("location: news.php");
        }
        break;

    case 'clean' :
        ////////////////////////////////////////////////////////////
        // Чистка новостей                                        //
        ////////////////////////////////////////////////////////////
        if ($rights >= 7) {
            echo '<div class="phdr">Чистка новостей</div>';
            if (isset ($_POST['submit'])) {
                $cl = isset ($_POST['cl']) ? intval($_POST['cl']) : '';
                switch ($cl) {
                    case '1' :
                        // Чистим новости, старше 1 недели
                        mysql_query("DELETE FROM `news` WHERE `time`<='" . ($realtime - 604800) . "'");
                        mysql_query("OPTIMIZE TABLE `news`;");
                        echo '<p>Удалены все новости, старше 1 дня.</p><p><a href="news.php">К новостям</a></p>';
                        break;

                    case '2' :
                        // Проводим полную очистку
                        mysql_query("TRUNCATE TABLE `news`");
                        echo '<p>Удалены все новости.</p><p><a href="news.php">К новостям</a></p>';
                        break;

                    default :
                        // Чистим сообщения, старше 1 месяца
                        mysql_query("DELETE FROM `news` WHERE `time`<='" . ($realtime - 2592000) . "'");
                        mysql_query("OPTIMIZE TABLE `news`;");
                        echo '<p>Удалены все новости, старше 1 недели.</p><p><a href="news.php">К новостям</a></p>';
                }
            }
            else {
                echo '<p><u>Что чистим?</u>';
                echo '<form id="clean" method="post" action="news.php?do=clean">';
                echo '<input type="radio" name="cl" value="0" checked="checked" />Старше 1 месяца<br />';
                echo '<input type="radio" name="cl" value="1" />Старше 1 недели<br />';
                echo '<input type="radio" name="cl" value="2" />Очищаем все<br />';
                echo '<input type="submit" name="submit" value="Очистить" />';
                echo '</form></p>';
                echo '<p><a href="news.php">Отмена</a></p>';
            }
        }
        else {
            header("location: news.php");
        }
        break;

    case 'del' :
        ////////////////////////////////////////////////////////////
        // Удаление новости                                       //
        ////////////////////////////////////////////////////////////
        if ($rights >= 6) {
            echo '<div class="phdr">Удалить новость</div>';
            if (isset ($_GET['yes'])) {
                mysql_query("DELETE FROM `news` WHERE `id` = '" . $id . "' LIMIT 1");
                echo '<p>Новость удалена!<br/><a href="news.php">К новостям</a></p>';
            }
            else {
                echo '<p>Вы уверены,что хотите удалить новость?<br/><a href="news.php?do=del&amp;id=' . $id . '&amp;yes">Да</a> | <a href="news.php">Нет</a></p>';
            }
        }
        else {
            header("location: news.php");
        }
        break;

    default :
        ////////////////////////////////////////////////////////////
        // Вывод списка новостей                                  //
        ////////////////////////////////////////////////////////////
        echo '<div class="phdr">Новости ресурса</div>';
        $req = mysql_query("SELECT COUNT(*) FROM `news`");
        $total = mysql_result($req, 0);
        $req = mysql_query("SELECT * FROM `news` ORDER BY `time` DESC LIMIT " . $start . "," . $kmess . ";");
        while ($nw1 = mysql_fetch_array($req)) {
            echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
            $text = $nw1['text'];
            $text = htmlentities($text, ENT_QUOTES, 'UTF-8');
            $text = str_replace("\r\n", "<br/>", $text);
            $text = tags($text);
            if ($set_user['smileys'])
                $text = smileys($text, 1);
            $vr = $nw1['time'] + $set_user['sdvig'] * 3600;
            $vr1 = date("d.m.y / H:i", $vr);
            echo '<b>' . $nw1['name'] . '</b><br/>' . $text . '<div class="func"><font color="#999999">Добавил: ' . $nw1['avt'] . ' (' . $vr1 . ')</font><br/>';
            if ($nw1['kom'] != 0 && $nw1['kom'] != "") {
                $mes = mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `refid` = '" . $nw1['kom'] . "'");
                $komm = mysql_result($mes, 0) - 1;
                if ($komm >= 0)
                    echo '<a href="../forum/?id=' . $nw1['kom'] . '">Обсудить на форуме (' . $komm . ')</a><br/>';
            }
            if ($rights >= 6) {
                echo '<a href="news.php?do=edit&amp;id=' . $nw1['id'] . '">Изменить</a> | <a href="news.php?do=del&amp;id=' . $nw1['id'] . '">Удалить</a>';
            }
            echo '</div></div>';
            ++$i;
        }
        echo '<div class="phdr">Всего:&nbsp;' . $total . '</div>';
        echo '<p>';
        if ($total > $kmess) {
            echo '<p>' . pagenav('news.php?', $start, $total, $kmess) . '</p>';
            echo '<p><form action="index.php" method="get"><input type="hidden" name="act" value="new"/><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
        }
        echo '</p>';
        if ($rights >= 6) {
            echo '<p><a href="news.php?do=add">Добавить новость</a>';
            if ($rights >= 7)
                echo '<br /><a href="news.php?do=clean">Чистка новостей</a>';
            echo '</p>';
        }
}

require_once ("../incfiles/end.php");

?>