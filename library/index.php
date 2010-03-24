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

$headmod = 'lib';
$textl = 'Библиотека';
require_once ("../incfiles/core.php");

// Ограничиваем доступ к Библиотеке
$error = '';
if (!$set['mod_lib'] && $rights < 7)
    $error = 'Библиотека закрыта';
elseif ($set['mod_lib'] == 1 && !$user_id)
    $error = 'Доступ в Библиотеку открыт только <a href="../login.php">авторизованным</a> посетителям';
if ($error) {
    require_once ("../incfiles/head.php");
    echo '<div class="rmenu"><p>' . $error . '</p></div>';
    require_once ("../incfiles/end.php");
    exit;
}

// Заголовки библиотеки
if (empty ($id)) {
    $textl = 'Библиотека';
}
else {
    $req = mysql_query("SELECT * FROM `lib` WHERE `id`= '" . $id . "' LIMIT 1;");
    $zag = mysql_fetch_array($req);
    $hdr = $zag['type'] == 'bk' ? $zag['name'] : $zag['text'];
    $hdr = htmlentities(mb_substr($hdr, 0, 30), ENT_QUOTES, 'UTF-8');
    $textl = mb_strlen($res['text']) > 30 ? $hdr . '...' : $hdr;
}
require_once ("../incfiles/head.php");

$do
    = array('java', 'symb', 'search', 'new', 'moder', 'addkomm', 'komm', 'del', 'edit', 'load', 'write', 'mkcat', 'topread', 'trans');
if (in_array($act, $do
        ) ) {
        require_once ($act . '.php');
}
else {
    if (!$set['mod_lib'])
        echo '<p><font color="#FF0000"><b>Библиотека закрыта!</b></font></p>';
    if (!$id) {
        echo '<div class="phdr"><b>Библиотека</b></div>';
        if ($rights == 5 || $rights >= 6) {
            // Считаем число статей, ожидающих модерацию
            $req = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = '0'");
            $res = mysql_result($req, 0);
            if ($res > 0)
                echo '<div class="rmenu">Модерации ожидают <a href="index.php?act=moder">' . $res . '</a> статей</div>';
        }
        // Сколько суток считать статьи новыми?
        $old = $realtime - (3 * 24 * 3600);
        // Считаем новое в библиотеке
        $req = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `time` > '" . $old . "' AND `type`='bk' AND `moder`='1'");
        $res = mysql_result($req, 0);
        echo '<div class="gmenu"><p>';
        if ($res > 0)
            echo '<a href="index.php?act=new">Новые статьи</a> (' . $res . ')<br/>';
        echo '<a href="index.php?act=topread">Самые читаемые</a></p></div>';
        $id = 0;
        $tip = "cat";
    }
    else {
        $tip = $zag['type'];
        if ($tip == "cat") {
            echo '<div class="phdr"><b>' . htmlentities($zag['text'], ENT_QUOTES, 'UTF-8') . '</b></div>';
        }
    }
    switch ($tip) {
        case 'cat' :
            $req = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'cat' AND `refid` = '" . $id . "'");
            $totalcat = mysql_result($req, 0);
            $bkz = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `refid` = '" . $id . "' AND `moder`='1'");
            $totalbk = mysql_result($bkz, 0);
            if ($totalcat > 0) {
                $total = $totalcat;
                $req = mysql_query("SELECT `id`, `text`  FROM `lib` WHERE `type` = 'cat' AND `refid` = '" . $id . "' LIMIT " . $start . "," . $kmess);
                while ($cat1 = mysql_fetch_array($req)) {
                    $cat2 = mysql_query("select `id` from `lib` where type = 'cat' and refid = '" . $cat1['id'] . "'");
                    $totalcat2 = mysql_num_rows($cat2);
                    $bk2 = mysql_query("select `id` from `lib` where type = 'bk' and refid = '" . $cat1['id'] . "' and moder='1'");
                    $totalbk2 = mysql_num_rows($bk2);
                    if ($totalcat2 != 0) {
                        $kol = "$totalcat2 кат.";
                    }
                    elseif ($totalbk2 != 0) {
                        $kol = "$totalbk2 ст.";
                    }
                    else {
                        $kol = "0";
                    }
                    echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
                    echo '<a href="index.php?id=' . $cat1['id'] . '">' . $cat1['text'] . '</a>(' . $kol . ')</div>';
                    ++$i;
                }
                echo '<div class="phdr">Всего категорий: ' . $totalcat . '</div>';
            }
            elseif ($totalbk > 0) {
                $total = $totalbk;
                $bk = mysql_query("select * from `lib` where type = 'bk' and refid = '" . $id . "' and moder='1' order by `time` desc LIMIT " . $start . "," . $kmess);
                while ($bk1 = mysql_fetch_array($bk)) {
                    echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
                    $vr = $bk1['time'] + $set_user['sdvig'] * 3600;
                    $vr = date("d.m.y / H:i", $vr);
                    echo $div . '<b><a href="index.php?id=' . $bk1['id'] . '">' . htmlentities($bk1['name'], ENT_QUOTES, 'UTF-8') . '</a></b><br/>';
                    echo htmlentities($bk1['announce'], ENT_QUOTES, 'UTF-8') . '<br />';
                    echo 'Добавил: ' . $bk1['avtor'] . ' (' . $vr . ')<br />';
                    echo 'Прочтений: ' . $bk1['count'] . '</div>';
                    ++$i;
                }
                echo '<div class="phdr">Всего статей: ' . $totalbk . '</div>';
            }
            else {
                $total = 0;
            }
            echo '<p>';
            // Навигация по страницам
            if ($total > $kmess) {
                echo '<p>' . pagenav('index.php?id=' . $id . '&amp;', $start, $total, $kmess) . '</p>';
                echo '<p><form action="index.php" method="get"><input type="hidden" name="id" value="' . $id . '"/><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
            }
            if (($rights == 5 || $rights >= 6) && $id != 0) {
                $ct = mysql_query("select `id` from `lib` where type='cat' and refid='" . $id . "'");
                $ct1 = mysql_num_rows($ct);
                if ($ct1 == 0) {
                    echo "<a href='index.php?act=del&amp;id=" . $id . "'>Удалить категорию</a><br/>";
                }
                echo "<a href='index.php?act=edit&amp;id=" . $id . "'>Изменить категорию</a><br/>";
            }
            if (($rights == 5 || $rights >= 6) && ($zag['ip'] == 1 || $id == 0)) {
                echo "<a href='index.php?act=mkcat&amp;id=" . $id . "'>Создать категорию</a><br/>";
            }
            if ($zag['ip'] == 0 && $id != 0) {
                if (($rights == 5 || $rights >= 6) || ($zag['soft'] == 1 && !empty ($_SESSION['uid']))) {
                    echo "<a href='index.php?act=write&amp;id=" . $id . "'>Написать статью</a><br/>";
                }
                if ($rights == 5 || $rights >= 6) {
                    echo "<a href='index.php?act=load&amp;id=" . $id . "'>Выгрузить статью</a><br/>";
                }
            }
            if ($id != 0) {
                $dnam = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $id . "'");
                $dnam1 = mysql_fetch_array($dnam);
                $dnam2 = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $dnam1['refid'] . "'");
                $dnam3 = mysql_fetch_array($dnam2);
                $catname = "$dnam3[text]";
                $dirid = "$dnam1[id]";

                $nadir = $dnam1['refid'];
                while ($nadir != "0") {
                    echo "&#187;<a href='index.php?id=" . $nadir . "'>$catname</a><br/>";
                    $dnamm = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $nadir . "'");
                    $dnamm1 = mysql_fetch_array($dnamm);
                    $dnamm2 = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $dnamm1['refid'] . "'");
                    $dnamm3 = mysql_fetch_array($dnamm2);
                    $nadir = $dnamm1['refid'];
                    $catname = $dnamm3['text'];
                }
                echo "&#187;<a href='index.php?'>В библиотеку</a><br/>";
            }
            else {
                echo "<a href='index.php?act=symb'>Настройки</a><br/>";
                echo "<form action='?act=search' method='post'>";
                echo
                "Поиск статьи: <br/><input type='text' name='srh' value=''/><br/>Метод поиска:<br/><select name='mod'><option value='1'>По названию</option><option value='2'>По тексту</option></select><br/>";
                echo "<input type='submit' value='Найти!'/></form><br/>";
            }
            echo '</p>';
            break;

        case 'bk' :
            ////////////////////////////////////////////////////////////
            // Читаем статью                                          //
            ////////////////////////////////////////////////////////////
            if (!empty ($_SESSION['symb'])) {
                $simvol = $_SESSION['symb'];
            }
            else {
                $simvol = 2000;                // Число символов на страницу по умолчанию
            }
            // Счетчик прочтений
            if ($_SESSION['lib'] != $id) {
                $_SESSION['lib'] = $id;
                $libcount = intval($zag['count']) + 1;
                mysql_query("UPDATE `lib` SET  `count` = '" . $libcount . "' WHERE `id` = '" . $id . "'");
            }
            // Заголовок статьи
            echo '<p><b>' . htmlentities($zag['name'], ENT_QUOTES, 'UTF-8') . '</b></p>';
            // Постраничная навигация читаемой статьи
            // Используется модифицированный код от hintoz
            $tx = $zag['text'];
            $strrpos = mb_strrpos($tx, " ");
            $pages = 1;
            // Вычисляем номер страницы
            if (isset ($_GET['page'])) {
                $page = abs(intval($_GET['page']));
                if ($page == 0)
                    $page = 1;
                $start = $page - 1;
            }
            else {
                $page = $start + 1;
            }
            $t_si = 0;
            if ($strrpos) {
                while ($t_si < $strrpos) {
                    $string = mb_substr($tx, $t_si, $simvol);
                    $t_ki = mb_strrpos($string, " ");
                    $m_sim = $t_ki;
                    $strings[$pages] = $string;
                    $t_si = $t_ki + $t_si;
                    if ($page == $pages) {
                        $page_text = $strings[$pages];
                    }
                    if ($strings[$pages] == "") {
                        $t_si = $strrpos++;
                    }
                    else {
                        $pages++;
                    }
                }
                if ($page >= $pages) {
                    $page = $pages - 1;
                    $page_text = $strings[$page];
                }
                $pages = $pages - 1;
                if ($page != $pages) {
                    $prb = mb_strrpos($page_text, " ");
                    $page_text = mb_substr($page_text, 0, $prb);
                }
            }
            else {
                $page_text = $tx;
            }
            // Текст статьи
            $page_text = htmlentities($page_text, ENT_QUOTES, 'UTF-8');
            echo '<p>' . nl2br($page_text) . '</p>';
            echo '<hr /><p>';
            if ($pages > 1) {
                echo '<p>' . pagenav('index.php?id=' . $id . '&amp;', $start, $pages, 1) . '</p>';
                echo '<p><form action="index.php" method="get"><input type="hidden" name="id" value="' . $id . '"/><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
            }
            if ($rights == 5 || $rights >= 6) {
                echo '<p><a href="index.php?act=edit&amp;id=' . $id . '">Редактировать</a><br/>';
                echo '<a href="index.php?act=del&amp;id=' . $id . '">Удалить статью</a></p>';
            }
            // Ссылка на комментарии
            if ($set['mod_lib_comm'] || $rights >= 7) {
                $km = mysql_query("select `id` from `lib` where type = 'komm' and refid = '" . $id . "'");
                $km1 = mysql_num_rows($km);
                echo "<a href='index.php?act=komm&amp;id=" . $id . "'>Комментарии</a>($km1)<br />";
            }
            echo '<a href="index.php?act=java&amp;id=' . $id . '">Скачать Java книгу</a><br /><br />';
            $dnam = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $zag['refid'] . "'");
            $dnam1 = mysql_fetch_array($dnam);
            $catname = "$dnam1[text]";
            $dirid = "$dnam1[id]";
            $nadir = $zag['refid'];
            while ($nadir != "0") {
                echo "&#187;<a href='index.php?id=" . $nadir . "'>$catname</a><br/>";
                $dnamm = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $nadir . "'");
                $dnamm1 = mysql_fetch_array($dnamm);
                $dnamm2 = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $dnamm1['refid'] . "'");
                $dnamm3 = mysql_fetch_array($dnamm2);
                $nadir = $dnamm1['refid'];
                $catname = $dnamm3['text'];
            }
            echo "&#187;<a href='index.php?'>В библиотеку</a></p>";
            break;

        default :
            header("location: index.php");
            break;
    }
}

require_once ('../incfiles/end.php');

?>