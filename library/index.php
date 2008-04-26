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

$headmod = 'lib';
$textl = 'Библиотека';
require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");

$act = isset($_GET['act']) ? $_GET['act'] : '';
switch ($act)
{
    case 'java':
        $id = intval(trim($_GET['id']));
        $req = mysql_query("select `name`, `text` from `lib` where `id` = '" . $id . "' and `type` = 'bk' and `moder`='1' LIMIT 1;");
        if (mysql_num_rows($req) == 0)
        {
            echo '<p>Статья не найдена</p>';
            require_once ("../incfiles/end.php");
            exit;
        }
        $res = mysql_fetch_array($req);

        // Создаем JAR файл
        if (!file_exists('files/' . $id . '.jar'))
        {
            $midlet_name = mb_substr($res['name'], 0, 10);
            $midlet_name = iconv('UTF-8', 'windows-1251', $midlet_name);

            // Записываем текст статьи
            $files = fopen("java/textfile.txt", 'w+');
            flock($files, LOCK_EX);
            $book_name = iconv('UTF-8', 'windows-1251', $res['name']);
            $book_text = iconv('UTF-8', 'windows-1251', $res['text']);
            $result = "\r\n" . $book_name . "\r\n\r\n----------\r\n\r\n" . $book_text . "\r\n\r\nDownloaded from $home";
            fputs($files, $result);
            flock($files, LOCK_UN);
            fclose($files);

            // Записываем манифест
            $manifest_text = 'Manifest-Version: 1.0
MIDlet-1: Книга ' . $id . ', , br.BookReader
MIDlet-Name: Книга ' . $id . '
MIDlet-Vendor: JohnCMS
MIDlet-Version: 1.5.3
MIDletX-No-Command: true
MIDletX-LG-Contents: true
MicroEdition-Configuration: CLDC-1.0
MicroEdition-Profile: MIDP-1.0
TCBR-Platform: Generic version (all phones)';
            $files = fopen("java/META-INF/MANIFEST.MF", 'w+');
            flock($files, LOCK_EX);
            fputs($files, $manifest_text);
            flock($files, LOCK_UN);
            fclose($files);

            // Создаем архив
            require_once ('../incfiles/pclzip.php');
            $archive = new PclZip('files/' . $id . '.jar');
            $list = $archive->create('java', PCLZIP_OPT_REMOVE_PATH, 'java');
            if (!file_exists('files/' . $id . '.jar'))
            {
                echo '<p>Ошибка создания JAR файла</p>';
                require_once ("../incfiles/end.php");
                exit;
            }
        }

        // Создаем JAD файл
        if (!file_exists('files/' . $id . '.jad'))
        {
            $filesize = filesize('files/' . $id . '.jar');
            $jad_text = 'Manifest-Version: 1.0
MIDlet-1: Книга ' . $id . ', , br.BookReader
MIDlet-Name: Книга ' . $id . '
MIDlet-Vendor: JohnCMS
MIDlet-Version: 1.5.3
MIDletX-No-Command: true
MIDletX-LG-Contents: true
MicroEdition-Configuration: CLDC-1.0
MicroEdition-Profile: MIDP-1.0
TCBR-Platform: Generic version (all phones)
MIDlet-Jar-Size: ' . $filesize . '
MIDlet-Jar-URL: ' . $home . '/library/files/' . $id . '.jar';
            $files = fopen('files/' . $id . '.jad', 'w+');
            flock($files, LOCK_EX);
            fputs($files, $jad_text);
            flock($files, LOCK_UN);
            fclose($files);
        }

        echo 'На этой странице Вы можете скачать Java (MIDP-2) книгу с нужной вам статьей.<br /><br />';
        echo 'Название: ' . $res['name'] . '<br />';
        echo 'Скачать: <a href="files/' . $id . '.jar">JAR</a> | <a href="files/' . $id . '.jad">JAD</a>';
        echo '<p><a href="index.php?id=' . $id . '">К статье</a></p>';
        break;

    case "symb":
        if (isset($_POST['submit']))
        {
            if (!empty($_POST['simvol']))
            {
                $simvol = intval($_POST['simvol']);
            }
            $_SESSION['symb'] = $simvol;
            echo "На время текущей сессии <br/>принято количество символов на страницу: $simvol <br/>";
        } else
        {
            echo "<form action='?act=symb' method='post'>
	Выберите количество символов на страницу:<br/><select name='simvol'>";
            if (!empty($_SESSION['symb']))
            {
                $realr = $_SESSION['symb'];
                echo "<option value='" . $realr . "'>" . $realr . "</option>";
            }
            echo "<option value='500'>500</option>
<option value='1000'>1000</option>
<option value='2000'>2000</option>
<option value='3000'>3000</option>
<option value='4000'>4000</option>
	</select><br/>
<input type='submit' name='submit' value='ok'/></form>";
        }
        echo "&#187;<a href='?'>К категориям</a><br/>";
        break;

    case "search":
        $_SESSION['lib'] = rand(1000, 9999);
        if (!empty($_POST['srh']))
        {
            $srh = trim($_POST['srh']);
        } else
        {
            echo "Вы не ввели условие поиска!<br/><a href='?'>К категориям</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        if (mb_strlen($_POST['srh']) < 2)
        {
            echo "В запросе на поиск должно быть не менее 2-х символов.<br/><a href='?'>К категориям</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        $mod = isset($_POST['mod']) ? intval($_POST['mod']):
        1;

        $psk = mysql_query("select * from `lib` where  type='bk' and moder='1';");
        $res = array();
        while ($array = mysql_fetch_array($psk))
        {
            switch ($mod)
            {
                case 1:
                    if (stristr($array[name], $srh))
                    {
                        $arrname = htmlentities($array[name], ENT_QUOTES, 'UTF-8');
                        $res[] = '<br/><a href="index.php?id=' . $array[id] . '">' . $arrname . '</a><br/>';
                    }
                    break;

                case 2:
                    $pg = mb_strlen($tx);
                    if (!empty($_SESSION['symb']))
                    {
                        $simvol = $_SESSION['symb'];
                    } else
                    {
                        $simvol = 600;
                    }
                    $page = ceil($pg / $simvol);
                    $tx = $array[text];
                    if (stristr($tx, $srh))
                    {
                        $arrname = htmlentities($array[name], ENT_QUOTES, 'UTF-8');
                        $tx = htmlentities($tx, ENT_QUOTES, 'UTF-8');
                        $a = mb_strpos($tx, $srh);
                        $page = ceil($a / $simvol) + 1;
                        if ($a > 100)
                        {
                            $a1 = $a - 100;
                            $a2 = 200;
                        } else
                        {
                            $a1 = 0;
                            $a2 = 100;
                        }
                        $tx = mb_substr($tx, $a1, $a2);
                        $b = mb_strpos($tx, " ");
                        $b2 = mb_strrpos($tx, " ");
                        $b1 = mb_strlen($tx);
                        $tx = mb_substr($tx, $b, $b2 - $b);
                        $tx = str_replace($srh1, "<b>$srh1</b>", $tx);
                        $tx = "...$tx...";
                        $res[] = "<a href='?id=" . $array[id] . "&amp;page=" . $page . "'>$arrname</a><br/>$tx<br/>";
                    }
                    break;

                default:
                    header("location: index.php");
                    break;
            }
        }
        $g = count($res);
        if ($g == 0)
        {
            echo "<br/>По вашему запросу ничего не найдено<br/>";
        } else
        {
            $srh = htmlentities($srh, ENT_QUOTES, 'UTF-8');
            echo "<b>Результаты поиска</b><br/><br/>Условие поиска: <b>$srh</b><br/>Метод поиска: ";

            if ($mod == 1)
            {
                echo "по названию<hr/>";
            } else
            {
                echo "по тексту<hr/>";
            }
        }
        if (empty($_GET['page']))
        {
            $page = 1;
        } else
        {
            $page = intval($_GET['page']);
        }
        $start = $page * 10 - 10;
        if ($g < $start + 10)
        {
            $end = $g;
        } else
        {
            $end = $start + 10;
        }
        for ($i = $start; $i < $end; $i++)
        {
            $d = $i / 2;
            $d1 = ceil($d);
            $d2 = $d1 - $d;
            $d3 = ceil($d2);
            if ($d3 == 0)
            {
                $div = "<div class='c'>";
            } else
            {
                $div = "<div class='b'>";
            }
            echo "$div $res[$i]</div>";
        }
        echo "<hr/>";
        if ($g > 10)
        {
            $ba = ceil($g / 10);
            if ($offpg != 1)
            {
                echo "Страницы:<br/>";
            } else
            {
                echo "Страниц: $ba<br/>";
            }
            $asd = $start - 10;
            $asd2 = $start + 20;
            if ($start != 0)
            {
                echo '<a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
            }
            if ($offpg != 1)
            {
                if ($asd < $g && $asd > 0)
                {
                    echo ' <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=1&amp;">1</a> .. ';
                }
                $page2 = $ba - $page;
                $pa = ceil($page / 2);
                $paa = ceil($page / 3);
                $pa2 = $page + floor($page2 / 2);
                $paa2 = $page + floor($page2 / 3);
                $paa3 = $page + (floor($page2 / 3) * 2);
                if ($page > 13)
                {
                    echo ' <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . $paa . '">' . $paa . '</a> <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($paa + 1) . '">' . ($paa + 1) .
                        '</a> .. <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($paa * 2) . '">' . ($paa * 2) . '</a> <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($paa * 2 + 1) . '">' . ($paa *
                        2 + 1) . '</a> .. ';
                } elseif ($page > 7)
                {
                    echo ' <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . $pa . '">' . $pa . '</a> <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($pa + 1) . '">' . ($pa + 1) .
                        '</a> .. ';
                }
                for ($i = $asd; $i < $asd2; )
                {
                    if ($i < $g && $i >= 0)
                    {
                        $ii = floor(1 + $i / 10);
                        if ($start == $i)
                        {
                            echo " <b>$ii</b>";
                        } else
                        {
                            echo ' <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . $ii . '">' . $ii . '</a> ';
                        }
                    }
                    $i = $i + 10;
                }
                if ($page2 > 12)
                {
                    echo ' .. <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) .
                        '</a> .. <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($paa3) . '">' . ($paa3) . '</a> <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($paa3 + 1) . '">' . ($paa3 +
                        1) . '</a> ';
                } elseif ($page2 > 6)
                {
                    echo ' .. <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) .
                        '</a> ';
                }
                if ($asd2 < $g)
                {
                    echo ' .. <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . $ba . '">' . $ba . '</a>';
                }
            } else
            {
                echo "<b>[$page]</b>";
            }
            if ($g > $start + 10)
            {
                echo ' <a href="index.php?act=search&amp;mod=' . $mod . '&amp;srh=' . $srh . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
            }
            echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='act' value='search'/><input type='hidden' name='srh' value='" . $srh . "'/><input type='hidden' name='mod' value='" . $mod .
                "'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
        }
        if ($g != 0)
        {
            echo "<br/>Найдено совпадений: $g";
        }
        echo '<br/><a href="?">К категориям</a><br/>';
        break;

    case "new":
        echo "<p><b>Новые статьи</b></p><hr/>";
        $old = $realtime - (3 * 24 * 3600);
        $newfile = mysql_query("select * from `lib` where `time` > '" . $old . "' and `type`='bk' and `moder`='1' order by `time` desc;");
        $totalnew = mysql_num_rows($newfile);
        if (empty($_GET['page']))
        {
            $page = 1;
        } else
        {
            $page = intval($_GET['page']);
        }
        $start = $page * 10 - 10;
        if ($totalnew < $start + 10)
        {
            $end = $totalnew;
        } else
        {
            $end = $start + 10;
        }
        if ($totalnew != 0)
        {
            while ($newf = mysql_fetch_array($newfile))
            {
                if ($i >= $start && $i < $end)
                {
                    $d = $i / 2;
                    $d1 = ceil($d);
                    $d2 = $d1 - $d;
                    $d3 = ceil($d2);
                    if ($d3 == 0)
                    {
                        $div = "<div class='c'>";
                    } else
                    {
                        $div = "<div class='b'>";
                    }
                    $vr = $newf['time'] + $sdvig * 3600;
                    $vr = date("d.m.y / H:i", $vr);
                    echo $div;
                    echo '<b><a href="?id=' . $newf['id'] . '">' . htmlentities($newf['name'], ENT_QUOTES, 'UTF-8') . '</a></b><br/>';
                    echo htmlentities($newf['announce'], ENT_QUOTES, 'UTF-8') . '<br />';
                    echo 'Добавил: ' . $newf['avtor'] . ' (' . $vr . ')<br/>';
                    $nadir = $newf['refid'];
                    $dirlink = $nadir;
                    $pat = "";
                    while ($nadir != "0")
                    {
                        $dnew = mysql_query("select * from `lib` where type = 'cat' and id = '" . $nadir . "';");
                        $dnew1 = mysql_fetch_array($dnew);
                        $pat = $dnew1['text'] . '/' . $pat;
                        $nadir = $dnew1['refid'];
                    }
                    $l = mb_strlen($pat);
                    $pat1 = mb_substr($pat, 0, $l - 1);
                    echo '[<a href="index.php?id=' . $dirlink . '">' . $pat1 . '</a>]</div>';
                }
                ++$i;
            }
            echo "<hr/><p>";
            if ($totalnew > 10)
            {
                $ba = ceil($totalnew / 10);
                if ($offpg != 1)
                {
                    echo "Страницы:<br/>";
                } else
                {
                    echo "Страниц: $ba<br/>";
                }
                if ($start != 0)
                {
                    echo '<a href="index.php?act=new&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
                }
                $asd = $start - 10;
                $asd2 = $start + 20;
                if ($offpg != 1)
                {
                    if ($asd < $totalnew && $asd > 0)
                    {
                        echo ' <a href="index.php?act=new&amp;page=1">1</a> .. ';
                    }
                    $page2 = $ba - $page;
                    $pa = ceil($page / 2);
                    $paa = ceil($page / 3);
                    $pa2 = $page + floor($page2 / 2);
                    $paa2 = $page + floor($page2 / 3);
                    $paa3 = $page + (floor($page2 / 3) * 2);
                    if ($page > 13)
                    {
                        echo ' <a href="index.php?act=new&amp;page=' . $paa . '">' . $paa . '</a> <a href="index.php?act=new&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="index.php?act=new&amp;page=' . ($paa * 2) . '">' . ($paa * 2) .
                            '</a> <a href="index.php?act=new&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
                    } elseif ($page > 7)
                    {
                        echo ' <a href="index.php?act=new&amp;page=' . $pa . '">' . $pa . '</a> <a href="index.php?act=new&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
                    }
                    for ($i = $asd; $i < $asd2; )
                    {
                        if ($i < $totalnew && $i >= 0)
                        {
                            $ii = floor(1 + $i / 10);
                            if ($start == $i)
                            {
                                echo " <b>$ii</b>";
                            } else
                            {
                                echo ' <a href="index.php?act=new&amp;page=' . $ii . '">' . $ii . '</a> ';
                            }
                        }
                        $i = $i + 10;
                    }
                    if ($page2 > 12)
                    {
                        echo ' .. <a href="index.php?act=new&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="index.php?act=new&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="index.php?act=new&amp;page=' . ($paa3) . '">' . ($paa3) .
                            '</a> <a href="index.php?act=new&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
                    } elseif ($page2 > 6)
                    {
                        echo ' .. <a href="index.php?act=new&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="?act=new&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
                    }
                    if ($asd2 < $totalnew)
                    {
                        echo ' .. <a href="index.php?act=new&amp;page=' . $ba . '">' . $ba . '</a>';
                    }
                } else
                {
                    echo "<b>[$page]</b>";
                }
                if ($totalnew > $start + 10)
                {
                    echo ' <a href="index.php?act=new&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
                }
                echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='act' value='new'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
            }
            if ($totalnew >= 1)
            {
                echo "Всего новых статей за 3 дня: $totalnew";
            }
        } else
        {
            echo "За три дня новых статей не было<br/>";
        }
        echo "<br/><a href='index.php?'>В библиотеку</a></p>";
        break;

    case "moder":
        if ($dostlmod == 1)
        {
            echo "<br/>Модерация статей<br/>";
            if ((!empty($_GET['id'])) && (isset($_GET['yes'])))
            {
                $id = intval(trim($_GET['id']));
                mysql_query("update `lib` set moder='1' , time='" . $realtime . "' where id='" . $id . "';");
                $fn = mysql_query("select `id`, `text` from `lib` where id='" . $id . "';");
                $fn1 = mysql_fetch_array($fn);
                echo "Статья $fn1[name] добавлена в базу<br/>";
            }
            if (isset($_GET['all']))
            {
                $mod = mysql_query("select `id` from `lib` where type='bk' and moder='0' ;");
                while ($modadd = mysql_fetch_array($mod))
                {
                    mysql_query("update `lib` set moder='1' , time='" . $realtime . "' where id='" . $modadd[id] . "';");
                }
                echo "Все файлы добавлены в базу<br/>";
            }

            $mdz = mysql_query("select `id` from `lib` where type='bk' and moder='0' ;");
            $mdz1 = mysql_num_rows($mdz);
            $ba = ceil($mdz1 / 10);
            if (empty($_GET['page']))
            {
                $page = 1;
            } else
            {
                $page = intval($_GET['page']);
            }
            if ($page < 1)
            {
                $page = 1;
            }
            if ($page > $ba)
            {
                $page = $ba;
            }
            $start = $page * 10 - 10;
            if ($mdz1 < $start + 10)
            {
                $end = $mdz1;
            } else
            {
                $end = $start + 10;
            }
            if ($mdz1 != 0)
            {
                $md = mysql_query("select `id`, `refid`, `avtor`, `text`, `soft`, `name`, `time` from `lib` where type='bk' and moder='0' LIMIT " . $start . "," . $end . ";");


                while ($md2 = mysql_fetch_array($md))
                {

                    $d = $i / 2;
                    $d1 = ceil($d);
                    $d2 = $d1 - $d;
                    $d3 = ceil($d2);
                    if ($d3 == 0)
                    {
                        $div = "<div class='c'>";
                    } else
                    {
                        $div = "<div class='b'>";
                    }
                    $vr = $md2[time] + $sdvig * 3600;
                    $vr = date("d.m.y / H:i", $vr);
                    $tx = $md2[soft];
                    echo "$div<a href='index.php?id=" . $md2[id] . "'>$md2[name]</a><br/>Добавил: $md2[avtor] ($vr)<br/>$tx <br/>";
                    $nadir = $md2['refid'];
                    $pat = "";
                    while ($nadir != "0")
                    {
                        $dnew = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $nadir . "';");
                        $dnew1 = mysql_fetch_array($dnew);
                        $pat = "$dnew1[text]/$pat";
                        $nadir = $dnew1['refid'];
                    }
                    $l = mb_strlen($pat);
                    $pat1 = mb_substr($pat, 0, $l - 1);
                    echo "[$pat1]<br/><a href='index.php?act=moder&amp;id=" . $md2['id'] . "&amp;yes'> Принять</a></div>";

                    ++$i;
                }
                if ($md1 > 10)
                {
                    echo "<hr/>";

                    if ($offpg != 1)
                    {
                        echo "Страницы:<br/>";
                    } else
                    {
                        echo "Страниц: $ba<br/>";
                    }

                    if ($start != 0)
                    {
                        echo '<a href="index.php?act=moder&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
                    }
                    if ($offpg != 1)
                    {
                        navigate('index.php?act=moder', $mdz1, 10, $start, $page);
                    } else
                    {
                        echo "<b>[$page]</b>";
                    }
                    if ($md1 > $start + 10)
                    {
                        echo ' <a href="index.php?act=moder&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
                    }
                    echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='act' value='moder'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
                }
                if ($md1 >= 1)
                {
                    echo "<br/>Всего: $mdz1";
                }
                echo "<br/><a href='index.php?act=moder&amp;all'>Принять все!</a><br/>";
            }
        } else
        {
            echo "Нет доступа!<br/>";
        }
        echo "<a href='?'>К категориям</a><br/>";
        break;

    case "addkomm":
        if (!empty($_SESSION['uid']))
        {
            if ($_GET['id'] == "")
            {
                echo "Не выбрана статья<br/><a href='?'>К категориям</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            $id = intval(trim($_GET['id']));
            if (isset($_POST['submit']))
            {
                $flt = $realtime - 30;
                $af = mysql_query("select `id` from `lib` where type='komm' and time>'" . $flt . "' and avtor= '" . $login . "';");
                $af1 = mysql_num_rows($af);
                if ($af1 != 0)
                {
                    echo "Антифлуд!Вы не можете так часто добавлять сообщения<br/>Порог 30 секунд<br/><a href='index.php?act=komm&amp;id=" . $id . "'>К комментариям</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                if ($_POST['msg'] == "")
                {
                    echo "Вы не ввели сообщение!<br/><a href='index.php?act=komm&amp;id=" . $id . "'>К комментариям</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                $msg = check(trim($_POST['msg']));
                if ($_POST[msgtrans] == 1)
                {
                    $msg = trans($msg);
                }
                $msg = mb_substr($msg, 0, 500);
                $agn = strtok($agn, ' ');
                mysql_query("insert into `lib` (
                refid,
                time,
                type,
                avtor,
                text,
                ip,
                soft
				) values(
				'" . $id . "',
				'" . $realtime . "',
				'komm',
				'" . $login . "',
				'" . $msg . "',
				'" . $ipl . "',
				'" . mysql_real_escape_string($agn) . "');");
                $fpst = $datauser['komm'] + 1;
                mysql_query("UPDATE `users` SET  `komm`='" . $fpst . "' WHERE `id`='" . $user_id . "';");
                echo '<p>Комментарий успешно добавлен';
            } else
            {
                echo "<p>Напишите комментарий<br/><br/><form action='?act=addkomm&amp;id=" . $id . "' method='post'>
Cообщение(max. 500)<br/>
<textarea rows='3' name='msg'></textarea><br/><br/>
<input type='checkbox' name='msgtrans' value='1' /> Транслит<br/>
<input type='submit' name='submit' value='добавить' />  
  </form><br/>";
                echo "<a href='index.php?act=trans'>Транслит</a><br /><a href='../str/smile.php'>Смайлы</a><br/>";
            }
        } else
        {
            echo "Вы не авторизованы!<br/>";
        }
        echo '<br/><a href="?act=komm&amp;id=' . $id . '">К комментариям</a></p>';
        break;

    case "trans":
        include ("../pages/trans.$ras_pages");
        echo '<br/><br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a><br/>';
        break;

    case "komm":
        if ($_GET['id'] == "")
        {
            echo "Не выбрана статья<br/><a href='?'>К категориям</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        $id = intval(check(trim($_GET['id'])));
        $messz = mysql_query("select `id` from `lib` where type='komm' and refid='" . $id . "'  ;");
        $countm = mysql_num_rows($messz);
        $ba = ceil($countm / $kmess);
        $fayl = mysql_query("select `name` from `lib` where type='bk' and id='" . $id . "';");
        $fayl1 = mysql_fetch_array($fayl);
        echo "<p>Комментируем статью:<br /><b>$fayl1[name]</b><br/>";
        if (!empty($_SESSION['uid']))
        {
            echo "</p><p><a href='index.php?act=addkomm&amp;id=" . $id . "'>Написать</a>";
        }
        echo '</p><hr/>';
        if (empty($_GET['page']))
        {
            $page = 1;
        } else
        {
            $page = intval($_GET['page']);
        }
        if ($page < 1)
        {
            $page = 1;
        }
        if ($page > $ba)
        {
            $page = $ba;
        }
        $start = $page * $kmess - $kmess;
        if ($countm < $start + $kmess)
        {
            $end = $countm;
        } else
        {
            $end = $start + $kmess;
        }
        $mess = mysql_query("select * from `lib` where type='komm' and refid='" . $id . "' order by time desc LIMIT " . $start . "," . $end . ";");
        while ($mass = mysql_fetch_array($mess))
        {

            $d = $i / 2;
            $d1 = ceil($d);
            $d2 = $d1 - $d;
            $d3 = ceil($d2);
            if ($d3 == 0)
            {
                $div = "<div class='c'>";
            } else
            {
                $div = "<div class='b'>";
            }
            $uz = @mysql_query("select * from `users` where name='" . check($mass[avtor]) . "';");
            $mass1 = @mysql_fetch_array($uz);
            echo "$div";
            if ((!empty($_SESSION['uid'])) && ($_SESSION['uid'] != $mass1['id']))
            {
                echo "<a href='anketa.php?user=" . $mass1['id'] . "'>$mass[avtor]</a>";
            } else
            {
                echo "$mass[avtor]";
            }
            $vr = $mass[time] + $sdvig * 3600;
            $vr1 = date("d.m.Y / H:i", $vr);
            switch ($mass1['rights'])
            {
                case 7:
                    echo ' Adm ';
                    break;
                case 6:
                    echo ' Smd ';
                    break;
                case 5:
                    echo ' Mod ';
                    break;
                case 1:
                    echo ' Kil ';
                    break;
            }
            $ontime = $mass1['lastdate'];
            $ontime2 = $ontime + 300;
            if ($realtime > $ontime2)
            {
                echo '<font color="#FF0000"> [Off]</font>';
            } else
            {
                echo '<font color="#00AA00"> [ON]</font>';
            }
            echo "($vr1)<br/>";
            tegi($mass['text']);

            if ($offsm != 1 && $offgr != 1)
            {
                $tekst = smiles($mass[text]);
                $tekst = smilescat($tekst);

                if ($mass['from'] == nickadmina || $mass['from'] == nickadmina2 || $mass1['rights'] >= 1)
                {
                    $tekst = smilesadm($tekst);
                }
            } else
            {
                $tekst = $mass[text];
            }
            echo "$tekst<br/>";
            if ($dostlmod == 1)
            {
                echo long2ip($mass['ip']) . " - $mass[soft]<br/><a href='index.php?act=del&amp;id=" . $mass[id] . "'>(Удалить)</a>";
            }
            echo "</div>";

            ++$i;
        }
        echo "<hr/><p>";
        if ($countm > $kmess)
        {
            if ($offpg != 1)
            {
                echo "Страницы:<br/>";
            } else
            {
                echo "Страниц: $ba<br/>";
            }

            if ($start != 0)
            {
                echo '<a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
            }
            if ($offpg != 1)
            {
                navigate('index.php?act=komm&amp;id=' . $id . '', $countm, $kmess, $start, $page);
            } else
            {
                echo "<b>[$page]</b>";
            }
            if ($countm > $start + $kmess)
            {
                echo ' <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
            }
            echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='id' value='" . $id .
                "'/><input type='hidden' name='act' value='komm'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
        }
        echo "Всего комментариев: $countm";
        echo '<br/><a href="?id=' . $id . '">К статье</a></p>';
        break;

    case "del":
        if ($dostlmod == 1)
        {
            if ($_GET['id'] == "" || $_GET['id'] == "0")
            {
                echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            $id = intval(trim($_GET['id']));
            $typ = mysql_query("select * from `lib` where id='" . $id . "';");
            $ms = mysql_fetch_array($typ);
            $rid = $ms[refid];
            if (isset($_GET['yes']))
            {
                switch ($ms[type])
                {
                    case "komm":
                        mysql_query("delete from `lib` where `id`='" . $id . "';");
                        header("location: index.php?act=komm&id=$rid");
                        break;
                    case "bk":
                        $km = mysql_query("select `id` from `lib` where type='komm' and refid='" . $id . "';");
                        while ($km1 = mysql_fetch_array($km))
                        {
                            mysql_query("delete from `lib` where `id`='" . $km1[id] . "';");
                        }
                        mysql_query("delete from `lib` where `id`='" . $id . "';");
                        header("location: index.php?id=$rid");
                        break;
                    case "cat":
                        $ct = mysql_query("select `id` from `lib` where type='cat' and refid='" . $id . "';");
                        $ct1 = mysql_num_rows($ct);
                        if ($ct1 != 0)
                        {
                            echo "Сначала удалите вложенные категории<br/><a href='index.php?id=" . $id . "'>Назад</a><br/>";
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        $st = mysql_query("select `id` from `lib` where type='bk' and refid='" . $id . "';");
                        while ($st1 = mysql_fetch_array($st))
                        {
                            $km = mysql_query("select `id` from `lib` where type='komm' and refid='" . $st1[id] . "';");
                            while ($km1 = mysql_fetch_array($km))
                            {
                                mysql_query("delete from `lib` where `id`='" . $km1[id] . "';");
                            }

                            mysql_query("delete from `lib` where `id`='" . $st1[id] . "';");
                        }
                        mysql_query("delete from `lib` where `id`='" . $id . "';");
                        header("location: index.php?id=$rid");
                        break;
                }
            } else
            {
                switch ($ms[type])
                {
                    case "komm":
                        header("location: index.php?act=del&id=$id&yes");
                        break;
                    case "bk":
                        echo "Вы уверены в удалении статьи?<br/><a href='index.php?act=del&amp;id=" . $id . "&amp;yes'>Да</a> | <a href='index.php?id=" . $id . "'>Нет</a><br/><a href='index.php'>В галерею</a><br/>";
                        break;
                    case "cat":
                        $ct = mysql_query("select `id` from `lib` where type='cat' and refid='" . $id . "';");
                        $ct1 = mysql_num_rows($ct);
                        if ($ct1 != 0)
                        {
                            echo "Сначала удалите вложенные категории<br/><a href='index.php?id=" . $id . "'>Назад</a><br/>";
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        echo "Вы уверены в удалении категории?<br/><a href='index.php?act=del&amp;id=" . $id . "&amp;yes'>Да</a> | <a href='index.php?id=" . $id . "'>Нет</a><br/><a href='index.php'>В галерею</a><br/>";
                        break;
                }
            }
        } else
        {
            header("location: index.php");
        }
        break;

    case "edit":
        if ($dostlmod == 1)
        {
            if ($_GET['id'] == "" || $_GET['id'] == "0")
            {
                echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            $id = intval(trim($_GET['id']));
            $typ = mysql_query("select * from `lib` where id='" . $id . "';");
            $ms = mysql_fetch_array($typ);
            if (isset($_POST['submit']))
            {
                switch ($ms[type])
                {
                    case "bk":
                        $name = check($_POST['name']);
                        $name = mb_substr($name, 0, 50);
                        $anons = check($_POST['anons']);
                        $anons = mb_substr($anons, 0, 100);
                        mysql_query("update `lib` set name='" . $name . "', soft='" . $anons . "' where id='" . $id . "';");
                        header("location: index.php?id=$ms[refid]");
                        break;
                    case "cat":
                        $text = check($_POST['text']);

                        if (!empty($_POST['user']))
                        {
                            $user = intval(check($_POST['user']));
                        } else
                        {
                            $user = 0;
                        }
                        $mod = intval(check($_POST['mod']));
                        mysql_query("update `lib` set text='" . $text . "',ip='" . $mod . "',soft='" . $user . "' where id='" . $id . "';");
                        header("location: index.php?id=$id");
                        break;
                    default:
                        $text = check($_POST['text']);
                        mysql_query("update `lib` set text='" . $text . "' where id='" . $id . "';");
                        header("location: index.php?id=$ms[refid]");
                        break;
                }
            } else
            {
                switch ($ms[type])
                {
                    case "bk":
                        echo "Редактируем название статьи<br/><form action='index.php?act=edit&amp;id=" . $id . "' method='post'>Название:<br/><input type='text' name='name' value='" . $ms[name] . "'/><br/>Анонс:<br/><input type='text' name='anons' value='" . $ms[soft] .
                            "'/><br/><input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=" . $id . "'>Назад</a><br/>";
                        break;
                    case "komm":
                        echo "Редактируем пост<br/><form action='index.php?act=edit&amp;id=" . $id . "' method='post'>Изменить:<br/><input type='text' name='text' value='" . $ms[text] .
                            "'/><br/><input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=" . $ms[refid] . "'>Назад</a><br/>";
                        break;
                    case "cat":

                        echo "Редактируем категорию<br/><form action='index.php?act=edit&amp;id=" . $id . "' method='post'>Изменить:<br/><input type='text' name='text' value='" . $ms[text] .
                            "'/><br/>Тип категории(во избежание глюков перед изменением типа очистите категорию!!!):<br/><select name='mod'>";

                        if ($ms[ip] == 1)
                        {
                            echo "<option value='1'>Категории</option><option value='0'>Статьи</option>";
                        } else
                        {
                            echo "<option value='0'>Статьи</option><option value='1'>Категории</option>";
                        }
                        echo "</select><br/>";
                        if ($ms[soft] == 1)
                        {
                            echo "Разрешить юзерам добавлять свои статьи<br/><input type='checkbox' name='user' value='1' checked='checked' /><br/>";
                        } else
                        {
                            echo "Разрешить юзерам добавлять свои статьи<br/><input type='checkbox' name='user' value='1'/><br/>";
                        }

                        echo "<input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=" . $ms[refid] . "'>Назад</a><br/>";
                        break;
                }
            }
        } else
        {
            header("location: index.php");
        }
        break;

    case "load":
        if ($dostlmod == 1)
        {
            if ($_GET['id'] == "")
            {
                echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            $id = intval(trim($_GET['id']));
            $typ = mysql_query("select * from `lib` where id='" . $id . "';");
            $ms = mysql_fetch_array($typ);
            if ($id != 0 && $ms[type] != "cat")
            {
                echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            if ($ms[ip] == 0)
            {
                if (isset($_POST['submit']))
                {
                    if (empty($_POST['name']))
                    {
                        echo "Вы не ввели название!<br/><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                        require_once ('../incfiles/end.php');
                        exit;
                    }
                    $name = mb_substr($_POST['name'], 0, 50);
                    $fname = $_FILES['fail']['name'];
                    $ftip = format($fname);
                    $ftip = strtolower($ftip);
                    if ($fname != "")
                    {
                        if (eregi("[^a-z0-9.()+_-]", $fname))
                        {
                            echo "В названии файла <b>$fname</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        if ((preg_match("/.php/i", $fname)) or (preg_match("/.pl/i", $fname)) or ($fname == ".htaccess"))
                        {
                            echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        if ($ftip != "txt")
                        {
                            echo "Это не текст .txt .<br/><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "temp/$fname")) == true)
                        {
                            $ch = $fname;
                            @chmod("$ch", 0777);
                            @chmod("temp/$ch", 0777);
                            $txt = file_get_contents("temp/$ch ");
                            if (mb_check_encoding($txt, 'UTF-8'))
                            {
                            } elseif (mb_check_encoding($txt, 'windows-1251'))
                            {
                                $txt = iconv("windows-1251", "UTF-8", $txt);
                            } elseif (mb_check_encoding($txt, 'KOI8-R'))
                            {
                                $txt = iconv("KOI8-R", "UTF-8", $txt);
                            } else
                            {
                                echo "Файл в неизвестной кодировке!<br /><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                                require_once ('../incfiles/end.php');
                                exit;
                            }
                            if (!empty($_POST['anons']))
                            {
                                $anons = mb_substr($_POST['anons'], 0, 100);
                            } else
                            {
                                $anons = mb_substr($txt, 0, 100);
                            }
                            mysql_query("insert into `lib` set
							`refid`='" . $id . "',
							`time`='" . $realtime . "',
							`type`='bk',
							`name`='" . mysql_real_escape_string($name) . "',
							`announce`='" . mysql_real_escape_string($anons) . "',
							`avtor`='" . $login . "',
							`text`='" . mysql_real_escape_string($txt) . "',
							`ip`='" . $ipl . "',
							`soft`='" . mysql_real_escape_string($agn) . "',
							`moder`='1';");
                            unlink("temp/$ch");
                            $cid = mysql_insert_id();
                            echo "Статья добавлена<br/><a href='index.php?id=" . $cid . "'>К статье</a><br/>";
                        } else
                        {
                            echo "Ошибка при загрузке<br/><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                    }
                    if (!empty($_POST['fail1']))
                    {
                        $libedfile = $_POST['fail1'];
                        if (strlen($libedfile) > 0)
                        {
                            $array = explode('file=', $libedfile);
                            $tmp_name = $array[0];
                            $filebase64 = $array[1];
                        }
                        $ftip = strtolower(format($tmp_name));
                        if (eregi("[^a-z0-9.()+_-]", $tmp_name))
                        {
                            echo "В названии файла <b>$fname</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        if ((preg_match("/.php/i", $fname)) or (preg_match("/.pl/i", $tmp_name)) or ($fname == ".htaccess"))
                        {
                            echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        if ($ftip != "txt")
                        {
                            echo "Это не текст .txt .<br/><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        if (strlen($filebase64) > 0)
                        {
                            $FileName = "temp/$tmp_name";
                            $filedata = base64_decode($filebase64);
                            $fid = @fopen($FileName, "wb");
                            if ($fid)
                            {
                                if (flock($fid, LOCK_EX))
                                {
                                    fwrite($fid, $filedata);
                                    flock($fid, LOCK_UN);
                                }
                                fclose($fid);
                            }
                            if (file_exists($FileName) && filesize($FileName) == strlen($filedata))
                            {
                                echo 'Файл загружен!<br/>';
                                $txt = file_get_contents("temp/$tmp_name");
                                if (mb_check_encoding($txt, 'windows-1251'))
                                {
                                    $txt = iconv("windows-1251", "UTF-8", $txt);

                                } elseif (mb_check_encoding($txt, 'KOI8-R'))
                                {
                                    $txt = iconv("KOI8-R", "UTF-8", $txt);
                                } elseif (mb_check_encoding($txt, 'UTF-8'))
                                {
                                    $txt = $txt;
                                } else
                                {
                                    echo "Файл в неизвестной кодировке!<br /><a href='index.php?act=load&amp;id=" . $id . "'>Повторить</a><br/>";
                                    require_once ('../incfiles/end.php');
                                    exit;
                                }
                                if (!empty($_POST['anons']))
                                {
                                    $anons = mb_substr($_POST['anons'], 0, 100);
                                } else
                                {
                                    $anons = mb_substr($txt, 0, 100);
                                }
                                mysql_query("INSERT INTO `lib` (
								refid,
								time,
								type,
								name,
								announce,
								text,
								avtor,
								ip,
								soft,
								moder
								) VALUES(
								'" . $id . "',
								'" . $realtime . "',
								'bk',
								'" . $name . "',
								'" . mysql_real_escape_string($anons) . "',
								'" . mysql_real_escape_string($txt) . "',
								'" . $login . "',
								'" . $ipl . "',
								'" . mysql_real_escape_string($agn) . "',
								'1'
								);");
                                unlink("temp/$tmp_name");
                                $cid = mysql_insert_id();
                                echo "Статья добавлена<br/><a href='index.php?id=" . $cid . "'>К статье</a><br/>";
                            } else
                            {
                                echo 'Ошибка при загрузке файла<br/>';
                            }
                        }
                    }
                } else
                {
                    echo "Выгрузка статьи<br/>(Поддерживаются кодировки Win-1251, KOI8-R, UTF-8)<br/><form action='index.php?act=load&amp;id=" . $id .
                        "' method='post' enctype='multipart/form-data'>Название статьи (max 50)<br/><input type='text' name='name'/><br/>Анонс (max 100)<br/><input type='text' name='anons'/><br/>Выберите текстовый файл( .txt):<br/><input type='file' name='fail'/><hr/>Для Opera Mini:<br/><input name='fail1' value =''/>&nbsp;<br/>
<a href='op:fileselect'>Выбрать файл</a>
<hr/><input type='submit' name='submit' value='Ok!'/><br/></form><a href ='index.php?id=" . $id . "'>Назад</a><br/>";
                }
            } else
            {
                echo "Ваще то эта категория не для статей,а для других категорий<br/>";
            }
        } else
        {
            header("location: index.php");
        }
        break;

    case "write":
        if ($_GET['id'] == "")
        {
            echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        $id = intval(trim($_GET['id']));
        $typ = mysql_query("select * from `lib` where id='" . $id . "';");
        $ms = mysql_fetch_array($typ);
        if ($id != 0 && $ms[type] != "cat")
        {
            echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        if ($ms['ip'] == 0)
        {
            if ($dostlmod == 1 || ($ms[soft] == 1 && !empty($_SESSION['uid'])))
            {
                if (isset($_POST['submit']))
                {
                    if (empty($_POST['name']))
                    {
                        echo "Вы не ввели название!<br/><a href='index.php?act=write&amp;id=" . $id . "'>Повторить</a><br/>";
                        require_once ('../incfiles/end.php');
                        exit;
                    }
                    if (empty($_POST['text']))
                    {
                        echo "Вы не ввели текст!<br/><a href='index.php?act=write&amp;id=" . $id . "'>Повторить</a><br/>";
                        require_once ('../incfiles/end.php');
                        exit;
                    }
                    $name = mb_substr($_POST['name'], 0, 50);
                    $text = $_POST['text'];
                    if (!empty($_POST['anons']))
                    {
                        $anons = mb_substr($_POST['anons'], 0, 100);
                    } else
                    {
                        $anons = mb_substr($text, 0, 100);
                    }
                    if ($dostlmod == 1)
                    {
                        $md = 1;
                    } else
                    {
                        $md = 0;
                    }
                    mysql_query("INSERT INTO `lib` (
					refid,
					time,
					type,
					name,
					announce,
					text,
					avtor,
					ip,
					soft,
					moder
					) VALUES(
					'" . $id . "',
					'" . $realtime . "',
					'bk',
					'" . mysql_real_escape_string($name) . "',
					'" . mysql_real_escape_string($anons) . "',
					'" . mysql_real_escape_string($text) . "',
					'" . $login . "',
					'" . $ipl . "',
					'" . mysql_real_escape_string($agn) . "',
					'" . $md . "'
					);");
                    $cid = mysql_insert_id();
                    if ($md == 1)
                    {
                        echo '<p>Статья добавлена</p>';
                    } else
                    {
                    	echo '<p>Статья добавлена<br/>Спасибо за то, что нам написали.</p><p>После проверки Модератором, Ваша статья будет опубликована в библиотеке.</p>';
                    }
                    echo '<p><a href="index.php?id=' . $cid . '">К статье</a></p>';
                } else
                {
                    echo "Добавление статьи<br/><form action='index.php?act=write&amp;id=" . $id .
                        "' method='post'>Введите название(max. 50):<br/><input type='text' name='name'/><br/>Анонс(max. 100):<br/><input type='text' name='anons'/><br/>Введите текст:<br/><textarea name='text' ></textarea><br/><input type='submit' name='submit' value='Ok!'/><br/></form><a href ='index.php?id=" .
                        $id . "'>Назад</a><br/>";
                }
            } else
            {
                header("location: index.php");
            }
        } else
        {
            echo "Ваще то эта категория не для статей,а для других категорий<br/>";
        }
        echo "<a href='index.php?'>В библиотеку</a><br/>";
        break;

    case "mkcat":
        if ($dostlmod == 1)
        {
            if ($_GET['id'] == "")
            {
                echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            $id = intval(trim($_GET['id']));
            $typ = mysql_query("select * from `lib` where id='" . $id . "';");
            $ms = mysql_fetch_array($typ);
            if ($id != 0 && ($ms[type] == "bk" || $ms[type] == "komm"))
            {
                echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            if (isset($_POST['submit']))
            {
                if (empty($_POST['text']))
                {
                    echo "Вы не ввели название!<br/><a href='index.php?act=mkcat&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                $text = check($_POST['text']);
                $user = intval($_POST['user']);
                $typs = intval($_POST['typs']);
                mysql_query("INSERT INTO `lib` (
				refid,
				time,
				type,
				text,
				ip,
				soft
				) VALUES(
				'" . $id . "',
				'" . $realtime . "',
				'cat',
				'" . $text . "',
				'" . $typs . "',
				'" . $user . "');");
                $cid = mysql_insert_id();
                echo "Категория создана<br/><a href='index.php?id=" . $cid . "'>В категорию</a><br/>";
            } else
            {

                echo "Добавление категории<br/><form action='index.php?act=mkcat&amp;id=" . $id .
                    "' method='post'>Введите название:<br/><input type='text' name='text'/><br/>Тип категории(для статей или вложенных категорий)<br/><select name='typs'><option value='1'>Категории</option><option value='0'>Статьи</option></select><hr/><input type='checkbox' name='user' value='1'/>Если тип-Статьи,разрешить юзерам добавлять свои статьи?<hr/><input type='submit' name='submit' value='Ok!'/><br/></form><a href ='index.php?id=" .
                    $id . "'>Назад</a><br/>";
            }
        } else
        {
            header("location: index.php");
        }
        break;

    case 'topread':
        // Рейтинг самых читаемых статей
        echo "<p><b>50 самых читаемых статей</b></p><hr/>";
        $req = mysql_query("select * from `lib` where `type` = 'bk' and `moder`='1' and `count`>'0' ORDER BY `count` DESC LIMIT 50;");
        $totalnew = mysql_num_rows($req);
        if (empty($_GET['page']))
        {
            $page = 1;
        } else
        {
            $page = intval($_GET['page']);
        }
        $start = $page * 10 - 10;
        if ($totalnew < $start + 10)
        {
            $end = $totalnew;
        } else
        {
            $end = $start + 10;
        }
        if ($totalnew != 0)
        {
            while ($res = mysql_fetch_array($req))
            {
                if ($i >= $start && $i < $end)
                {
                    $d = $i / 2;
                    $d1 = ceil($d);
                    $d2 = $d1 - $d;
                    $d3 = ceil($d2);
                    if ($d3 == 0)
                    {
                        $div = "<div class='c'>";
                    } else
                    {
                        $div = "<div class='b'>";
                    }
                    $vr = $newf['time'] + $sdvig * 3600;
                    $vr = date("d.m.y / H:i", $vr);
                    echo $div;
                    echo '<b><a href="?id=' . $res['id'] . '">' . htmlentities($res['name'], ENT_QUOTES, 'UTF-8') . '</a></b><br/>';
                    echo htmlentities($res['announce'], ENT_QUOTES, 'UTF-8') . '<br />';
                    echo 'Прочтений: ' . $res['count'] . '<br/>';
                    $nadir = $res['refid'];
                    $dirlink = $nadir;
                    $pat = "";
                    while ($nadir != "0")
                    {
                        $dnew = mysql_query("select * from `lib` where type = 'cat' and id = '" . $nadir . "';");
                        $dnew1 = mysql_fetch_array($dnew);
                        $pat = $dnew1['text'] . '/' . $pat;
                        $nadir = $dnew1['refid'];
                    }
                    $l = mb_strlen($pat);
                    $pat1 = mb_substr($pat, 0, $l - 1);
                    echo '[<a href="index.php?id=' . $dirlink . '">' . $pat1 . '</a>]</div>';
                }
                ++$i;
            }
            echo "<hr/><p>";
            if ($totalnew > 10)
            {
                $ba = ceil($totalnew / 10);
                if ($offpg != 1)
                {
                    echo "Страницы:<br/>";
                } else
                {
                    echo "Страниц: $ba<br/>";
                }
                if ($start != 0)
                {
                    echo '<a href="index.php?act=topread&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
                }
                $asd = $start - 10;
                $asd2 = $start + 20;
                if ($offpg != 1)
                {
                    if ($asd < $totalnew && $asd > 0)
                    {
                        echo ' <a href="index.php?act=topread&amp;page=1">1</a> .. ';
                    }
                    $page2 = $ba - $page;
                    $pa = ceil($page / 2);
                    $paa = ceil($page / 3);
                    $pa2 = $page + floor($page2 / 2);
                    $paa2 = $page + floor($page2 / 3);
                    $paa3 = $page + (floor($page2 / 3) * 2);
                    if ($page > 13)
                    {
                        echo ' <a href="index.php?act=topread&amp;page=' . $paa . '">' . $paa . '</a> <a href="index.php?act=topread&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="index.php?act=topread&amp;page=' . ($paa * 2) . '">' . ($paa * 2) .
                            '</a> <a href="index.php?act=topread&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
                    } elseif ($page > 7)
                    {
                        echo ' <a href="index.php?act=topread&amp;page=' . $pa . '">' . $pa . '</a> <a href="index.php?act=topread&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
                    }
                    for ($i = $asd; $i < $asd2; )
                    {
                        if ($i < $totalnew && $i >= 0)
                        {
                            $ii = floor(1 + $i / 10);
                            if ($start == $i)
                            {
                                echo " <b>$ii</b>";
                            } else
                            {
                                echo ' <a href="index.php?act=topread&amp;page=' . $ii . '">' . $ii . '</a> ';
                            }
                        }
                        $i = $i + 10;
                    }
                    if ($page2 > 12)
                    {
                        echo ' .. <a href="index.php?act=topread&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="index.php?act=topread&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="index.php?act=topread&amp;page=' . ($paa3) . '">' . ($paa3) .
                            '</a> <a href="index.php?act=topread&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
                    } elseif ($page2 > 6)
                    {
                        echo ' .. <a href="index.php?act=topread&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="?act=topread&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
                    }
                    if ($asd2 < $totalnew)
                    {
                        echo ' .. <a href="index.php?act=topread&amp;page=' . $ba . '">' . $ba . '</a>';
                    }
                } else
                {
                    echo "<b>[$page]</b>";
                }
                if ($totalnew > $start + 10)
                {
                    echo ' <a href="index.php?act=topread&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
                }
                echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='act' value='new'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
            }
        } else
        {
            echo "<p>Еще никто не читал в библиотеке<br/>";
        }
        echo "<a href='index.php?'>В библиотеку</a></p>";
        break;

    default:
        if ($dostlmod == 1)
        {
            $mod = mysql_query("select * from `lib` where type = 'bk' and moder = '0';");
            $mod1 = mysql_num_rows($mod);
            if ($mod1 > 0)
            {
                echo "<br/>Модерации ожидают <a href='index.php?act=moder'>$mod1</a> статей<br/>";
            }
        }
        if (empty($_GET['id']))
        {
            $old = $realtime - (3 * 24 * 3600); // Сколько суток считать статьи новыми?
            $fl1 = mysql_query("select * from `lib` where time > '" . $old . "' and type='bk' and moder='1';");
            $countf1 = mysql_num_rows($fl1);
            echo '<p>';
            if ($countf1 != 0)
                echo '<a href="index.php?act=new">Новые статьи</a> (' . $countf1 . ')<br/>';
            echo '<a href="index.php?act=topread">Самые читаемые</a></p><hr/>';
            $id = 0;
            $tip = "cat";
        } else
        {
            $id = intval(trim($_GET['id']));
            $tp = mysql_query("select * from `lib` where id = '" . $id . "';");
            $tp1 = mysql_fetch_array($tp);
            $tip = $tp1[type];
            if ($tp1[type] == "cat")
            {
                echo '<p><b>' . $tp1[text] . '</b></p><hr/>';
            }
        }
        switch ($tip)
        {
            case "cat":
                $catz = mysql_query("select `id`  from `lib` where type = 'cat' and refid = '" . $id . "';");
                $totalcat = mysql_num_rows($catz);
                $bkz = mysql_query("select `id` from `lib` where type = 'bk' and refid = '" . $id . "' and moder='1';");
                $totalbk = mysql_num_rows($bkz);
                if ($totalcat != 0)
                {
                    $total = $totalcat;
                    $ba = ceil($total / 10);
                    if (empty($_GET['page']))
                    {
                        $page = 1;
                    } else
                    {
                        $page = intval($_GET['page']);
                    }
                    if ($page < 1)
                    {
                        $page = 1;
                    }
                    if ($page > $ba)
                    {
                        $page = $ba;
                    }
                    $start = $page * 10 - 10;
                    if ($total < $start + 10)
                    {
                        $end = $total;
                    } else
                    {
                        $end = $start + 10;
                    }
                    $cat = mysql_query("select `id`, `text`  from `lib` where type = 'cat' and refid = '" . $id . "' LIMIT " . $start . "," . $end . ";");
                    $i = 0;
                    $mm = mysql_num_rows($cat);
                    while ($cat1 = mysql_fetch_array($cat))
                    {
                        $cat2 = mysql_query("select `id` from `lib` where type = 'cat' and refid = '" . $cat1[id] . "';");
                        $totalcat2 = mysql_num_rows($cat2);
                        $bk2 = mysql_query("select `id` from `lib` where type = 'bk' and refid = '" . $cat1[id] . "' and moder='1';");
                        $totalbk2 = mysql_num_rows($bk2);
                        if ($totalcat2 != 0)
                        {
                            $kol = "$totalcat2 кат.";
                        } elseif ($totalbk2 != 0)
                        {
                            $kol = "$totalbk2 ст.";
                        } else
                        {
                            $kol = "0";
                        }
                        echo '<div class="menu"><img alt="" src="../images/arrow.gif" width="7" height="12" />&nbsp;<a href="index.php?id=' . $cat1[id] . '">' . $cat1[text] . '</a>(' . $kol . ')</div>';
                        ++$i;
                    }
                } elseif ($totalbk != 0)
                {
                    $total = $totalbk;
                    $ba = ceil($total / 10);
                    if (empty($_GET['page']))
                    {
                        $page = 1;
                    } else
                    {
                        $page = intval($_GET['page']);
                    }
                    if ($page < 1)
                    {
                        $page = 1;
                    }
                    if ($page > $ba)
                    {
                        $page = $ba;
                    }
                    $start = $page * 10 - 10;
                    if ($total < $start + 10)
                    {
                        $end = $total;
                    } else
                    {
                        $end = $start + 10;
                    }
                    $bk = mysql_query("select * from `lib` where type = 'bk' and refid = '" . $id . "' and moder='1' order by time desc LIMIT " . $start . "," . $end . ";");
                    while ($bk1 = mysql_fetch_array($bk))
                    {
                        $d = $i / 2;
                        $d1 = ceil($d);
                        $d2 = $d1 - $d;
                        $d3 = ceil($d2);
                        if ($d3 == 0)
                        {
                            $div = "<div class='c'>";
                        } else
                        {
                            $div = "<div class='b'>";
                        }
                        $vr = $bk1[time] + $sdvig * 3600;
                        $vr = date("d.m.y / H:i", $vr);
                        echo $div . '<b><a href="index.php?id=' . $bk1['id'] . '">' . htmlentities($bk1['name'], ENT_QUOTES, 'UTF-8') . '</a></b><br/>';
                        echo htmlentities($bk1['announce'], ENT_QUOTES, 'UTF-8') . '<br />';
                        echo 'Добавил: ' . $bk1['avtor'] . ' (' . $vr . ')';
                        echo '</div>';
                        ++$i;
                    }
                } else
                {
                    $total = 0;
                }
                echo "<hr/><p>";
                if ($total > 10)
                {
                    if ($offpg != 1)
                    {
                        echo "Страницы:<br/>";
                    } else
                    {
                        echo "Страниц: $ba<br/>";
                    }
                    if ($start != 0)
                    {
                        echo '<a href="index.php?id=' . $id . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
                    }
                    if ($offpg != 1)
                    {
                        navigate('index.php?id=' . $id . '', $total, 10, $start, $page);
                    } else
                    {
                        echo "<b>[$page]</b>";
                    }
                    if ($total > $start + 10)
                    {
                        echo ' <a href="index.php?id=' . $id . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
                    }
                    echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='id' value='" . $id .
                        "'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
                }
                if ($total != 0)
                {
                    if ($totalcat >= 1)
                    {
                        echo 'Всего категорий: ' . $totalcat . '<br/>';
                    } elseif ($totalbk >= 1)
                    {
                        echo 'Всего статей: ' . $totalbk . '<br/>';
                    }
                } else
                {
                    echo 'В данной категории нет статей!<br/>';
                }
                if ($dostlmod == 1 && $id != 0)
                {
                    $ct = mysql_query("select `id` from `lib` where type='cat' and refid='" . $id . "';");
                    $ct1 = mysql_num_rows($ct);
                    if ($ct1 == 0)
                    {
                        echo "<a href='index.php?act=del&amp;id=" . $id . "'>Удалить категорию</a><br/>";
                    }
                    echo "<a href='index.php?act=edit&amp;id=" . $id . "'>Изменить категорию</a><br/>";
                }
                if ($dostlmod == 1 && ($tp1[ip] == 1 || $id == 0))
                {
                    echo "<a href='index.php?act=mkcat&amp;id=" . $id . "'>Создать категорию</a><br/>";
                }
                if ($tp1[ip] == 0 && $id != 0)
                {
                    if ($dostlmod == 1 || ($tp1[soft] == 1 && !empty($_SESSION['uid'])))
                    {
                        echo "<a href='index.php?act=write&amp;id=" . $id . "'>Написать статью</a><br/>";
                    }
                    if ($dostlmod == 1)
                    {
                        echo "<a href='index.php?act=load&amp;id=" . $id . "'>Выгрузить статью</a><br/>";
                    }
                }
                if ($id != 0)
                {
                    $dnam = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $id . "';");
                    $dnam1 = mysql_fetch_array($dnam);
                    $dnam2 = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $dnam1[refid] . "';");
                    $dnam3 = mysql_fetch_array($dnam2);
                    $catname = "$dnam3[text]";
                    $dirid = "$dnam1[id]";

                    $nadir = $dnam1[refid];
                    while ($nadir != "0")
                    {
                        echo "&#187;<a href='index.php?id=" . $nadir . "'>$catname</a><br/>";
                        $dnamm = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $nadir . "';");
                        $dnamm1 = mysql_fetch_array($dnamm);
                        $dnamm2 = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $dnamm1[refid] . "';");
                        $dnamm3 = mysql_fetch_array($dnamm2);
                        $nadir = $dnamm1[refid];
                        $catname = $dnamm3[text];
                    }
                    echo "&#187;<a href='index.php?'>В библиотеку</a><br/>";
                } else
                {
                    echo "<a href='index.php?act=symb'>Настройки</a><br/>";
                    echo "<form action='?act=search' method='post'>";
                    echo "Поиск статьи: <br/><input type='text' name='srh' value=''/><br/>Метод поиска:<br/><select name='mod'><option value='1'>По названию</option><option value='2'>По тексту</option></select><br/>";
                    echo "<input type='submit' value='Найти!'/></form><br/>";
                }
                echo '</p>';
                break;

            case "bk":
                ////////////////////////////////////////////////////////////
                // Читаем статью                                          //
                ////////////////////////////////////////////////////////////
                if (!empty($_SESSION['symb']))
                {
                    $simvol = $_SESSION['symb'];
                } else
                {
                    $simvol = 2000; // Число символов на страницу по умолчанию
                }
                if (!empty($_GET['page']))
                {
                    $page = intval($_GET['page']);
                } else
                {
                    $page = 1;
                }
                // Счетчик прочтений
                if ($_SESSION['lib'] != $id)
                {
                    $_SESSION['lib'] = $id;
                    $libcount = intval($tp1[count]) + 1;
                    mysql_query("update `lib` set  `count`='" . $libcount . "' where id='" . $id . "';");
                }

                // Заголовок статьи
                echo '<p><b>' . htmlentities($tp1[name], ENT_QUOTES, 'UTF-8') . '</b></p>';
                $tx = $tp1['text'];

                # для постраничного вывода используется модифицированный код от hintoz #
                $strrpos = mb_strrpos($tx, " ");
                $pages = 1;
                $t_si = 0;
                if ($strrpos)
                {
                    while ($t_si < $strrpos)
                    {
                        $string = mb_substr($tx, $t_si, $simvol);
                        $t_ki = mb_strrpos($string, " ");
                        $m_sim = $t_ki;
                        $strings[$pages] = $string;
                        $t_si = $t_ki + $t_si;
                        if ($page == $pages)
                        {
                            $page_text = $strings[$pages];
                        }
                        if ($strings[$pages] == "")
                        {
                            $t_si = $strrpos++;
                        } else
                        {
                            $pages++;
                        }
                    }
                    if ($page >= $pages)
                    {
                        $page = $pages - 1;
                        $page_text = $strings[$page];
                    }
                    $pages = $pages - 1;
                    if ($page != $pages)
                    {
                        $prb = mb_strrpos($page_text, " ");
                        $page_text = mb_substr($page_text, 0, $prb);
                    }
                } else
                {
                    $page_text = $tx;
                }
                // Текст статьи
                $page_text = htmlentities($page_text, ENT_QUOTES, 'UTF-8');
                $page_text = str_replace("\r\n", "<br />", $page_text);
                echo $page_text;
                echo '<hr /><p>';
                $next = $page + 1;
                $prev = $page - 1;
                if ($pages > 1)
                {
                    if ($offpg != 1)
                    {
                        echo "Страницы:<br/>";
                    } else
                    {
                        echo "Страниц: $pages<br/>";
                    }
                    if ($page > 1)
                    {
                        print " <a href=\"index.php?id=$id&amp;page=$prev\">&lt;&lt;</a> ";
                    }
                    if ($offpg != 1)
                    {
                        if ($page > 1)
                        {
                            print "<a href=\"index.php?id=$id&amp;page=1\">1</a> ";
                        }
                        if ($prev > 2)
                        {
                            print " .. ";
                        }
                        $page2 = $pages - $page;
                        $pa = ceil($page / 2);
                        $paa = ceil($page / 3);
                        $pa2 = $page + floor($page2 / 2);
                        $paa2 = $page + floor($page2 / 3);
                        $paa3 = $page + (floor($page2 / 3) * 2);
                        if ($page > 13)
                        {
                            echo ' <a href="index.php?id=' . $id . '&amp;page=' . $paa . '">' . $paa . '</a> <a href="index.php?id=' . $id . '&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="index.php?id=' . $id . '&amp;page=' . ($paa * 2) . '">' . ($paa *
                                2) . '</a> <a href="index.php?id=' . $id . '&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
                        } elseif ($page > 7)
                        {
                            echo ' <a href="index.php?id=' . $id . '&amp;page=' . $pa . '">' . $pa . '</a> <a href="index.php?id=' . $id . '&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
                        }
                        if ($prev > 1)
                        {
                            print "<a href=\"index.php?id=$id&amp;page=$prev\">$prev</a> ";
                        }
                        echo "<b>$page</b> ";
                        if ($next < $pages)
                        {
                            print "<a href=\"index.php?id=$id&amp;page=$next\">$next</a> ";
                        }
                        if ($page2 > 12)
                        {
                            echo ' .. <a href="index.php?id=' . $id . '&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="index.php?id=' . $id . '&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="index.php?id=' . $id . '&amp;page=' . ($paa3) . '">' . ($paa3) .
                                '</a> <a href="index.php?id=' . $id . '&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
                        } elseif ($page2 > 6)
                        {
                            echo ' .. <a href="index.php?id=' . $id . '&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="index.php?id=' . $id . '&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
                        }
                        if ($next < ($pages - 1))
                        {
                            print " .. ";
                        }
                        if ($page < $pages)
                        {
                            print "<a href=\"index.php?id=$id&amp;page=$pages\">$pages</a> ";
                        }
                    } else
                    {
                        echo "<b>[$page]</b>";
                    }
                    if ($page < $pages)
                    {
                        print " <a href=\"index.php?id=$id&amp;page=$next\">&gt;&gt;</a>";
                    }
                    echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='id' value='" . $id .
                        "'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
                }
                if ($dostlmod == 1)
                {
                    echo "<a href='index.php?act=del&amp;id=" . $id . "'>Удалить статью</a><br/>";
                    echo "<a href='index.php?act=edit&amp;id=" . $id . "'>Изменить название</a><br/><br/>";
                }
                $km = mysql_query("select `id` from `lib` where type = 'komm' and refid = '" . $id . "';");
                $km1 = mysql_num_rows($km);
                echo "<a href='index.php?act=komm&amp;id=" . $id . "'>Комментарии</a>($km1)<br />";
                echo '<a href="index.php?act=java&amp;id=' . $id . '">Скачать Java книгу</a><br /><br />';
                $dnam = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $tp1[refid] . "';");
                $dnam1 = mysql_fetch_array($dnam);
                $catname = "$dnam1[text]";
                $dirid = "$dnam1[id]";
                $nadir = $tp1[refid];
                while ($nadir != "0")
                {
                    echo "&#187;<a href='index.php?id=" . $nadir . "'>$catname</a><br/>";
                    $dnamm = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $nadir . "';");
                    $dnamm1 = mysql_fetch_array($dnamm);
                    $dnamm2 = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $dnamm1[refid] . "';");
                    $dnamm3 = mysql_fetch_array($dnamm2);
                    $nadir = $dnamm1[refid];
                    $catname = $dnamm3[text];
                }
                echo "&#187;<a href='index.php?'>В библиотеку</a></p>";
                break;

            default:
                header("location: index.php");
                break;
        }
        break;
}

require_once ('../incfiles/end.php');

?>