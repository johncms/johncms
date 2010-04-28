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

define('_IN_JOHNCMS', 1);

$headmod = 'load';
$textl = 'Загрузки';
require_once ("../incfiles/core.php");
require_once ("../incfiles/mp3.php");
require_once ("../incfiles/class_pclzip.php");
$filesroot = "../download";
$screenroot = "$filesroot/screen";
$loadroot = "$filesroot/files";

// Ограничиваем доступ к Загрузкам
$error = '';
if (!$set['mod_down'] && $rights < 7)
    $error = 'Загрузки закрыты';
elseif ($set['mod_down'] == 1 && !$user_id)
    $error = 'Доступ к загрузкам открыт только <a href="../login.php">авторизованным</a> посетителям';
if ($error) {
    require_once ("../incfiles/head.php");
    echo '<div class="rmenu"><p>' . $error . '</p></div>';
    require_once ("../incfiles/end.php");
    exit;
}

$do
    = array('scan_dir', 'rat', 'delmes', 'search', 'addkomm', 'komm', 'new', 'zip', 'arc', 'down', 'dfile', 'opis', 'renf', 'screen', 'ren', 'import', 'cut', 'refresh', 'upl', 'view', 'makdir', 'select', 'preview', 'delcat', 'mp3', 'trans');
if (in_array($act, $do
        ) ) {
        require_once ($act . '.php');
}
else {
    require_once ("../incfiles/head.php");
    if (!$set['mod_down'])
        echo '<p><font color="#FF0000"><b>Загруз-зона закрыта!</b></font></p>';
    // Ссылка на новые файлы
    $old = $realtime - (3 * 24 * 3600);
    echo '<p><a href="?act=new">Новые файлы</a> (' . mysql_result(mysql_query("SELECT COUNT(*) FROM `download` WHERE `time` > '" . $old . "' AND `type` = 'file'"), 0) . ')</p>';
    if (empty ($_GET['cat'])) {
        // Заголовок начальной страницы загрузок
        echo '<div class="phdr">Загрузки</div>';
    }
    else {
        // Заголовок страниц категорий
        $cat = intval($_GET['cat']);
        $req = mysql_query("SELECT * FROM `download` WHERE `type` = 'cat' AND `id` = '" . $cat . "' LIMIT 1");
        $res = mysql_fetch_array($req);
        if (mysql_num_rows($req) == 0 || !is_dir($res['adres'] . '/' . $res['name'])) {
            // Если неправильно выбран каталог, выводим ошибку
            echo '<p>ОШИБКА!<br />Каталог не существует<br /><a href="index.php">Назад</a></p>';
            require_once ('../incfiles/end.php');
            exit;
        }
        ////////////////////////////////////////////////////////////
        // Получаем структуру каталогов                           //
        ////////////////////////////////////////////////////////////
        $tree = array();
        $dirid = $cat;
        while ($dirid != '0' && $dirid != "") {
            $req = mysql_query("SELECT * FROM `download` WHERE `type` = 'cat' and `id` = '" . $dirid . "' LIMIT 1");
            $res = mysql_fetch_array($req);
            $tree[] = '<a href="index.php?cat=' . $dirid . '">' . $res['text'] . '</a>';
            $dirid = $res['refid'];
        }
        krsort($tree);
        $cdir = array_pop($tree);
        echo '<div class="phdr"><a href="index.php">Загрузки</a> | ';
        foreach ($tree as $value) {
            echo $value . ' | ';
        }
        echo '<b>' . strip_tags($cdir) . '</b></div>';
    }
    // Подсчитываем число папок
    $req = mysql_query("SELECT COUNT(*) FROM `download` WHERE `refid` = '" . $cat . "' AND `type` = 'cat'");
    $totalcat = mysql_result($req, 0);
    // Подсчитываем число файлов
    $req = mysql_query("SELECT COUNT(*) FROM `download` WHERE `refid` = '" . $cat . "' AND `type` = 'file'");
    $totalfile = mysql_result($req, 0);
    $total = $totalcat + $totalfile;
    if ($total > 0) {
        $zap = mysql_query("SELECT * FROM `download` WHERE `refid` = '" . $cat . "' ORDER BY `type` ASC, `text` ASC, `name` ASC LIMIT " . $start . "," . $kmess);
        while ($zap2 = mysql_fetch_array($zap)) {
        ////////////////////////////////////////////////////////////
        // Выводим список папок                                   //
        ////////////////////////////////////////////////////////////
            if ($totalcat > 0 && $zap2['type'] == 'cat') {
                echo '<div class="list1">';
                echo '<a href="?cat=' . $zap2['id'] . '">' . $zap2['text'] . '</a>';
                $g1 = 0;
                // Считаем число файлов в подкаталогах
                $req = mysql_query("SELECT COUNT(*) FROM `download` WHERE `type` = 'file' AND `adres` LIKE '" . ($zap2['adres'] . '/' . $zap2['name']) . "%'");
                $g = mysql_result($req, 0);
                // Считаем новые файлы в подкаталогах
                $old = $realtime - (3 * 24 * 3600);
                $req = mysql_query("SELECT COUNT(*) FROM `download` WHERE `type` = 'file' AND `adres` LIKE '" . ($zap2['adres'] . '/' . $zap2['name']) . "%' AND `time` > '" . $old . "'");
                $g1 = mysql_result($req, 0);
                echo "($g";
                if ($g1 != 0) {
                    echo "/+$g1)</div>";
                }
                else {
                    echo ")</div>";
                }
            }
            ////////////////////////////////////////////////////////////
            // Выводим cписок файлов                                  //
            ////////////////////////////////////////////////////////////
            if ($totalfile > 0 && $zap2['type'] == 'file') {
                echo '<div class="list2">';
                $ft = format($zap2['name']);
                switch ($ft) {
                    case "mp3" :
                        $imt = "mp3.png";
                        break;
                    case "zip" :
                        $imt = "rar.png";
                        break;
                    case "jar" :
                        $imt = "jar.png";
                        break;
                    case "gif" :
                        $imt = "gif.png";
                        break;
                    case "jpg" :
                        $imt = "jpg.png";
                        break;
                    case "png" :
                        $imt = "png.png";
                        break;
                    default :
                        $imt = "file.gif";
                        break;
                }
                echo '<img src="' . $filesroot . '/img/' . $imt . '" alt=""/><a href="?act=view&amp;file=' . $zap2['id'] . '">' . htmlentities($zap2['name'], ENT_QUOTES, 'UTF-8') . '</a>';
                if ($zap2['text'] != "") {
                    // Выводим анонс текстового описания (если есть)
                    $tx = $zap2['text'];
                    if (mb_strlen($tx) > 100) {
                        $tx = mb_substr($tx, 0, 90);
                        $tx .= '...';
                    }
                    echo '<div class="sub">' . $tx . '</div>';
                }
                echo '</div>';
            }
            ++$i;
        }
    }
    else {
        echo '<div class="menu"><p>В данной категории нет файлов</p></div>';
    }
    echo '<div class="phdr">';
    if ($totalcat > 0)
        echo 'Папок: ' . $totalcat;
    echo '&nbsp;&nbsp;&nbsp;';
    if ($totalfile > 0)
        echo 'Файлов: ' . $totalfile;
    echo '</div>';
    // Постраничная навигация
    if ($total > $kmess) {
        echo '<p>' . pagenav('index.php?cat=' . $cat . '&amp;', $start, $total, $kmess) . '</p>';
        echo '<p><form action="guest.php" method="get"><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
    }
    if ($rights == 4 || $rights >= 6) {
        ////////////////////////////////////////////////////////////
        // Выводим ссылки на модерские функции                    //
        ////////////////////////////////////////////////////////////
        echo '<p><div class="func">';
        echo '<a href="?act=makdir&amp;cat=' . $cat . '">Создать папку</a><br/>';
        if (!empty ($_GET['cat'])) {
            $delcat = mysql_query("select * from `download` where type = 'cat' and refid = '" . $cat . "';");
            $delcat1 = mysql_num_rows($delcat);
            if ($delcat1 == 0) {
                echo "<a href='?act=delcat&amp;cat=" . $cat . "'>Удалить каталог</a><br/>";
            }
            echo "<a href='?act=ren&amp;cat=" . $cat . "'>Переименовать каталог</a><br/>";
            echo "<a href='?act=select&amp;cat=" . $cat . "'>Выгрузить файл</a><br/>";
            echo "<a href='?act=import&amp;cat=" . $cat . "'>Импорт файла</a><br/>";
        }
        echo '<a href="?act=refresh">Обновить</a>';
        echo '</div></p>';
    }
    if (!empty ($cat))
        echo '<p><a href="index.php">В загрузки</a></p>';
    echo "<a href='?act=preview'>Размеры изображений</a><br/>";
    if (empty ($cat)) {
        echo "<form action='?act=search' method='post'>";
        echo "Поиск файла: <br/><input type='text' name='srh' size='20' maxlength='20' title='Введите запрос' value=''/><br/>";

        echo "<input type='submit' title='Нажмите для поиска' value='Найти!'/></form><br/>";
    }
}

require_once ('../incfiles/end.php');

?>