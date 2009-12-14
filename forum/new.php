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

$textl = 'Форум-новые';
$headmod = 'forumnew';
require_once ("../incfiles/head.php");
echo '<p><a href="index.php">Вернуться в форум</a></p>';
unset ($_SESSION['fsort_id']);
unset ($_SESSION['fsort_users']);
if (empty ($_SESSION['uid'])) {
    if (isset ($_GET['newup'])) {
        $_SESSION['uppost'] = 1;
    }
    if (isset ($_GET['newdown'])) {
        $_SESSION['uppost'] = 0;
    }
}
if ($user_id) {
    $do
        = isset ($_GET['do']) ? $_GET['do'] : '';
    switch ($do
            ) {
            case 'reset' :
                ////////////////////////////////////////////////////////////
                // Отмечаем все темы как прочитанные                      //
                ////////////////////////////////////////////////////////////
                $req = mysql_query("SELECT `forum`.`id`
            FROM `forum` LEFT JOIN `cms_forum_rdm` ON `forum`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $user_id .
                "'
            WHERE `forum`.`type`='t'
            AND `cms_forum_rdm`.`topic_id` Is Null");
                while ($res = mysql_fetch_array($req)) {
                    mysql_query("INSERT INTO `cms_forum_rdm` SET
				`topic_id`='" . $res['id'] . "',
				`user_id`='" . $user_id . "',
				`time`='" . $realtime . "'");
                }
                $req = mysql_query("SELECT `forum`.`id` AS `id`
			FROM `forum` LEFT JOIN `cms_forum_rdm` ON `forum`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $user_id .
                "'
			WHERE `forum`.`type`='t'
			AND `forum`.`time` > `cms_forum_rdm`.`time`");
                while ($res = mysql_fetch_array($req)) {
                    mysql_query("UPDATE `cms_forum_rdm` SET `time`='" . $realtime . "' WHERE `topic_id`='" . $res['id'] . "' AND `user_id`='" . $user_id . "'");
                }
                $_SESSION['fnew'] = 0;
                $_SESSION['fnewtime'] = time();
                echo '<p>Все темы приняты как прочитанные</p>';
                break;

            case 'select' :
            echo '<div class="phdr"><b>Показать за период</b></div>';
            echo '<div class="menu"><p><form action="index.php?act=new&amp;do=all" method="post">Период(в часах):<br/>';
            echo '<input type="text" maxlength="3" name="vr" value="24" size="3"/>';
            echo '<input type="hidden" name="act" value="all"/><input type="submit" name="submit" value="Показать"/></form></p></div>';
            echo '<div class="phdr"><a href="index.php?act=new">Назад</a></div>';
            break;

        case 'all' :
            $vr = isset ($_REQUEST['vr']) ? abs(intval($_REQUEST['vr'])) : null;
            if (!$vr) {
                echo "Вы не ввели время!<br/><a href='index.php?act=new&amp;do=all'>Повторить</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $vr1 = $realtime - $vr * 3600;
            if ($rights == 9) {
                $req = mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type`='t' AND `time` > '" . $vr1 . "'");
            }
            else {
                $req = mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type`='t' AND `time` > '" . $vr1 . "' AND `close` != '1'");
            }
            $count = mysql_result($req, 0);
            if ($count > 0) {
                echo '<div class="phdr"><b>Все за период ' . $vr . ' часов</b></div>';
                if ($rights == 9) {
                    $req = mysql_query("SELECT * FROM `forum` WHERE `type`='t' AND `time` > '" . $vr1 . "' ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
                }
                else {
                    $req = mysql_query("SELECT * FROM `forum` WHERE `type`='t' AND `time` > '" . $vr1 . "' AND `close` != '1' ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
                }
                $i = 0;
                while ($res = mysql_fetch_array($req)) {
                    echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
                    $q3 = mysql_query("SELECT `id`, `refid`, `text` FROM `forum` WHERE `type`='r' AND `id`='" . $res['refid'] . "'");
                    $razd = mysql_fetch_array($q3);
                    $q4 = mysql_query("SELECT `text` FROM `forum` WHERE `type`='f' AND `id`='" . $razd['refid'] . "'");
                    $frm = mysql_fetch_array($q4);
                    $colmes = mysql_query("SELECT * FROM `forum` WHERE `refid` = '" . $res['id'] . "' AND `type` = 'm'" . ($rights >= 7 ? '' : " AND `close` != '1'") . " ORDER BY `time` DESC");
                    $colmes1 = mysql_num_rows($colmes);
                    $cpg = ceil($colmes1 / $kmess);
                    $nick = mysql_fetch_array($colmes);
                    if ($res['edit'])
                        echo '<img src="../images/tz.gif" alt=""/>';
                    elseif ($res['close'])
                        echo '<img src="../images/dl.gif" alt=""/>';
                    else
                        echo '<img src="../images/np.gif" alt=""/>';
                    if ($res['realid'] == 1)
                        echo '&nbsp;<img src="../images/rate.gif" alt=""/>';
                    echo '&nbsp;<a href="index.php?id=' . $res['id'] . ($cpg > 1 && $set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] && $cpg > 1 ? '&amp;page=' . $cpg : '') . '">' . $res['text'] .
                    '</a>&nbsp;[' . $colmes1 . ']';
                    if ($cpg > 1)
                        echo '<a href="index.php?id=' . $res['id'] . (!$set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] ? '' : '&amp;page=' . $cpg) . '">&nbsp;&gt;&gt;</a>';
                    echo '<br /><div class="sub"><a href="index.php?id=' . $razd['id'] . '">' . $frm['text'] . '&nbsp;/&nbsp;' . $razd['text'] . '</a><br />';
                    echo $res['from'];
                    if ($colmes1 > 1) {
                        echo '&nbsp;/&nbsp;' . $nick['from'];
                    }
                    echo ' <font color="#777777">' . date("d.m.y / H:i", $nick['time']) . '</font>';
                    echo '</div></div>';
                    ++$i;
                }
                echo '<div class="phdr">Всего: ' . $count . '</div>';
                if ($count > $kmess) {
                    echo '<p>' . pagenav('index.php?act=new&amp;do=all&amp;vr=' . $vr . '&amp;', $start, $count, $kmess) . '</p>';
                    echo '<p><form action="index.php" method="get">
					<input type="hidden" name="act" value="new"/>
					<input type="hidden" name="do" value="all"/>
					<input type="hidden" name="vr" value="' . $vr .
                    '"/>
					<input type="text" name="page" size="2"/>
					<input type="submit" value="К странице &gt;&gt;"/></form></p>';
                }
            }
            else {
                echo '<p>За выбранный период нового  на форуме нет.</p>';
            }
            echo '<p><a href="index.php?act=new">Назад</a></p>';
            break;

        default :
            ////////////////////////////////////////////////////////////
            // Вывод непрочитанных тем (для зарегистрированных)       //
            ////////////////////////////////////////////////////////////
            $total = forum_new();
            if ($total > 0) {
                echo '<div class="phdr"><b>Непрочитанное</b></div>';
                $req = mysql_query("SELECT * FROM `forum`
                LEFT JOIN `cms_forum_rdm` ON `forum`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $user_id . "'
                WHERE `forum`.`type`='t'" .
                ($rights >= 7 ? "" : " AND `forum`.`close` != '1'") .
                "
                AND (`cms_forum_rdm`.`topic_id` Is Null
                OR `forum`.`time` > `cms_forum_rdm`.`time`)
                ORDER BY `forum`.`time` DESC
                LIMIT " . $start . "," . $kmess);
                while ($res = mysql_fetch_array($req)) {
                    echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
                    $q3 = mysql_query("SELECT `id`, `refid`, `text` FROM `forum` WHERE `type`='r' AND `id`='" . $res['refid'] . "'");
                    $razd = mysql_fetch_array($q3);
                    $q4 = mysql_query("SELECT `text` FROM `forum` WHERE `type`='f' AND `id`='" . $razd['refid'] . "'");
                    $frm = mysql_fetch_array($q4);
                    $colmes = mysql_query("SELECT * FROM `forum` WHERE `refid` = '" . $res['id'] . "' AND `type` = 'm'" . ($rights >= 7 ? '' : " AND `close` != '1'") . " ORDER BY `time` DESC");
                    $colmes1 = mysql_num_rows($colmes);
                    $cpg = ceil($colmes1 / $kmess);
                    $nick = mysql_fetch_array($colmes);
                    if ($res['edit'])
                        echo '<img src="../images/tz.gif" alt=""/>';
                    elseif ($res['close'])
                        echo '<img src="../images/dl.gif" alt=""/>';
                    else
                        echo '<img src="../images/np.gif" alt=""/>';
                    if ($res['realid'] == 1)
                        echo '&nbsp;<img src="../images/rate.gif" alt=""/>';
                    echo '&nbsp;<a href="index.php?id=' . $res['id'] . ($cpg > 1 && $set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] && $cpg > 1 ? '&amp;page=' . $cpg : '') . '">' . $res['text'] .
                    '</a>&nbsp;[' . $colmes1 . ']';
                    if ($cpg > 1)
                        echo '<a href="index.php?id=' . $res['id'] . (!$set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] ? '' : '&amp;page=' . $cpg) . '">&nbsp;&gt;&gt;</a>';
                    echo '<br /><div class="sub"><a href="index.php?id=' . $razd['id'] . '">' . $frm['text'] . '&nbsp;/&nbsp;' . $razd['text'] . '</a><br />';
                    echo $res['from'];
                    if ($colmes1 > 1) {
                        echo '&nbsp;/&nbsp;' . $nick['from'];
                    }
                    echo ' <font color="#777777">' . date("d.m.y / H:i", $nick['time']) . '</font>';
                    echo '</div></div>';
                    ++$i;
                }
                echo '<div class="phdr">Всего: ' . $total . '</div>';
                if ($total > $kmess) {
                    echo '<p>' . pagenav('index.php?act=new&amp;', $start, $total, $kmess) . '</p>';
                    echo '<p><form action="index.php" method="get"><input type="hidden" name="act" value="new"/><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
                }
                echo '<p><a href="index.php?act=new&amp;do=reset">Сброс!</a><br/>';
            }
            else {
                echo '<p>Непрочитанных тем нет.</p><p>';
            }
            echo '<a href="index.php?act=new&amp;do=select">Показать за период</a></p>';
            break;
    }
}
else {
    ////////////////////////////////////////////////////////////
    // Вывод непрочитанных тем (для незарегистрированных)     //
    ////////////////////////////////////////////////////////////
    echo '<div class="phdr"><b>Последние 10 тем</b></div>';
    $req = mysql_query("SELECT * FROM `forum` WHERE `type` = 't' AND `close`!='1' ORDER BY `time` DESC LIMIT 10");
    while ($arr = mysql_fetch_array($req)) {
        $q3 = mysql_query("select `id`, `refid`, `text` from `forum` where type='r' and id='" . $arr['refid'] . "'");
        $razd = mysql_fetch_array($q3);
        $q4 = mysql_query("select `id`, `refid`, `text` from `forum` where type='f' and id='" . $razd['refid'] . "'");
        $frm = mysql_fetch_array($q4);
        $nikuser = mysql_query("SELECT `from`, `time` FROM `forum` WHERE `type` = 'm' AND `close` != '1' AND `refid` = '" . $arr['id'] . "'ORDER BY time DESC");
        $colmes1 = mysql_num_rows($nikuser);
        $cpg = ceil($colmes1 / $kmess);
        $nam = mysql_fetch_array($nikuser);
        echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
        echo '<img src="../images/' . ($arr['edit'] == 1 ? 'tz' : 'np') . '.gif" alt=""/>';
        if ($arr['realid'] == 1)
            echo '&nbsp;<img src="../images/rate.gif" alt=""/>';
        echo '&nbsp;<a href="index.php?id=' . $arr['id'] . ($cpg > 1 && $_SESSION['uppost'] ? '&amp;clip&amp;page=' . $cpg : '') . '">' . $arr['text'] . '</a>&nbsp;[' . $colmes1 . ']';
        if ($cpg > 1)
            echo '&nbsp;<a href="index.php?id=' . $arr['id'] . ($_SESSION['uppost'] ? '' : '&amp;clip&amp;page=' . $cpg) . '">&gt;&gt;</a>';
        echo '<br/><div class="sub"><a href="index.php?id=' . $razd['id'] . '">' . $frm['text'] . '&nbsp;/&nbsp;' . $razd['text'] . '</a><br />';
        echo $arr['from'];
        if (!empty ($nam['from'])) {
            echo '&nbsp;/&nbsp;' . $nam['from'];
        }
        echo ' <font color="#777777">' . date("d.m.y / H:i", $nam['time']) . '</font>';
        echo '</div></div>';
        $i++;
    }
}

require_once ("../incfiles/end.php");

?>
