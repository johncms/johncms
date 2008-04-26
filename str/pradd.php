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
session_name('SESID');
session_start();
$textl = 'Почта(письма)';
require_once ("../incfiles/core.php");

$msg = check(trim($_POST['msg']));
if ($_POST[msgtrans] == 1)
{
    $msg = trans($msg);
}
$foruser = check(trim($_POST['foruser']));
$tem = check(trim($_POST['tem']));
$idm = intval(trim($_POST['idm']));
if (!empty($_SESSION['uid']))
{
    if (!empty($_GET['act']))
    {
        $act = check($_GET['act']);
    }
    switch ($act)
    {
        case "send":
            require_once ("../incfiles/head.php");
            $ign = mysql_query("select * from `privat` where me='" . $foruser . "' and ignor='" . $login . "';");
            $ign1 = mysql_num_rows($ign);
            if ($ign1 != 0)
            {
                echo "Вы не можете отправить письмо для $foruser ,поскольку находитесь в его игнор-листе!!!<br/><a href='privat.php'>В приват</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            if (!empty($foruser) and !empty($msg))
            {
                $m = mysql_query("select * from `users` where name='" . $foruser . "';");
                $count = mysql_num_rows($m);
                if ($count == 1)
                {
                    $messag = mysql_query("select * from `users` where name='" . $foruser . "';");
                    $us = mysql_fetch_array($messag);
                    $adres = $us[id];
                    $fname = $_FILES['fail']['name'];
                    $fsize = $_FILES['fail']['size'];
                    if ($fname != "")
                    {
                        $tfl = strtolower(format($fname));
                        $df = array("asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
                        if (in_array($tfl, $df))
                        {
                            echo "Попытка отправить файл запрещенного типа.<br/><a href='pradd.php?act=write&amp;adr=" . $adres . "'>Повторить</a><br/>";
                            require_once ("../incfiles/end.php");
                            exit;
                        }
                        if ($fsize >= 1024 * $flsz)
                        {
                            echo "Вес файла превышает $flsz кб<br/><a href='pradd.php?act=write&amp;adr=" . $adres . "'>Повторить</a><br/>";
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        if (eregi("[^a-z0-9.()+_-]", $fname))
                        {
                            echo "В названии файла <b>$fname</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br/><a href='pradd.php?act=write&amp;adr=" . $adres . "'>Повторить</a><br/>";
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        if ((preg_match("/php/i", $fname)) or (preg_match("/.pl/i", $fname)) or ($fname == ".htaccess"))
                        {
                            echo "Попытка отправить файл запрещенного типа.<br/><a href='pradd.php?act=write&amp;adr=" . $adres . "'>Повторить</a><br/>";
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "../pratt/$fname")) == true)
                        {
                            $ch = $fname;
                            @chmod("$ch", 0777);
                            @chmod("../pratt/$ch", 0777);
                            echo "Файл прикреплен!<br/>";
                        } else
                        {
                            echo "Ошибка при прикреплении файла<br/>";
                        }
                    }
                    $uploaddir = "../pratt";
                    $uploadedfile = $_POST['fail1'];
                    if (strlen($uploadedfile) > 0)
                    {
                        $array = explode('file=', $uploadedfile);
                        $tmp_name = $array[0];
                        $filebase64 = $array[1];
                    }
                    $tfl = strtolower(format($tmp_name));
                    $df = array("asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
                    if (in_array($tfl, $df))
                    {
                        echo "Попытка отправить файл запрещенного типа.<br/><a href='pradd.php?act=write&amp;adr=" . $adres . "'>Повторить</a><br/>";
                        require_once ("../incfiles/end.php");
                        exit;
                    }
                    if (strlen(base64_decode($filebase64)) >= 1024 * $flsz)
                    {
                        echo "Вес файла превышает $flsz кб<br/><a href='pradd.php?act=write&amp;adr=" . $adres . "'>Повторить</a><br/>";
                        require_once ('../incfiles/end.php');
                        exit;
                    }
                    if (eregi("[^a-z0-9.()+_-]", $tmp_name))
                    {
                        echo "В названии файла <b>$tmp_name</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='pradd.php?act=write&amp;adr=" . $adres . "'>Повторить</a><br/>";
                        require_once ('../incfiles/end.php');
                        exit;
                    }
                    if ((preg_match("/php/i", $tmp_name)) or (preg_match("/.pl/i", $tmp_name)) or ($tmp_name == ".htaccess"))
                    {
                        echo "Попытка отправить файл запрещенного типа.<br/><a href='pradd.php?act=write&amp;adr=" . $adres . "'>Повторить</a><br/>";
                        require_once ('../incfiles/end.php');
                        exit;
                    }
                    if (strlen($filebase64) > 0)
                    {
                        $fname = $tmp_name;
                        $FileName = "$uploaddir/$fname";
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
                            echo 'Файл ', $tmp_name, ' успешно прикреплён<br/>';
                            $ch = $fname;
                        } else
                        {
                            echo 'Ошибка при прикреплении файла ', $tmp_name, '<br/>';
                        }
                    }
                    mysql_query("insert into `privat` values(0,'" . $foruser . "','" . $msg . "','" . $realtime . "','" . $login . "','in','no','" . $tem . "','0','','','','" . $ch . "');");
                    mysql_query("insert into `privat` values(0,'" . $foruser . "','" . $msg . "','" . $realtime . "','" . $login . "','out','no','" . $tem . "','0','','','','" . $ch . "');");
                    if (!empty($idm))
                    {
                        mysql_query("update `privat` set otvet='1' where id='" . $idm . "';");
                    }
                    echo "<hr/>Письмо отправлено!<br/>";
                    if (!empty($_SESSION['refpr']))
                    {
                        echo "<a href='" . $_SESSION['refpr'] . "'>Вернуться откуда пришли</a><br/>";
                    }
                    $_SESSION['refpr'] = "";
                } else
                {
                    echo "Такого пользователя не существует<br/>";
                }
            } else
            {
                echo "Не введено имя пользователя или сообщение!<br/>";
            }
            break;

        case "load":
            $id = intval(check($_GET['id']));
            $fil = mysql_query("select * from `privat` where id='" . $id . "';");
            $mas = mysql_fetch_array($fil);
            $att = $mas[attach];
            if (!empty($att))
            {
                $tfl = strtolower(format(trim($att)));
                $df = array("asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
                if (in_array($tfl, $df))
                {
                    require_once ("../incfiles/head.php");
                    echo "Ошибка!<br/>&#187;<a href='pradd.php'>В приват</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                if (file_exists("../pratt/$att"))
                {
                    header("location: ../pratt/$att");
                }
            }
            break;

        case "write":
            // Форма для отправки привата
            require_once ("../incfiles/head.php");
            if (!empty($_GET['adr']))
            {
                $messages = mysql_query("select * from `users` where id='" . intval($_GET['adr']) . "';");
                $user = mysql_fetch_array($messages);
                $adresat = $user['name'];
                $tema = "Привет, $adresat!";
                $ign = mysql_query("select * from `privat` where me='" . $adresat . "' and ignor='" . $login . "';");
                $ign1 = mysql_num_rows($ign);
                if ($ign1 != 0)
                {
                    echo "Вы не можете отправить письмо для $adresat ,поскольку находитесь в его игнор-листе!!!<br/><a href='privat.php'>В приват</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
            } else
            {
                $tema = "Привет!";
            }
            if (!empty($_GET['id']))
            {
                $id = intval(check($_GET['id']));
                $messages2 = mysql_query("select * from `privat` where id='" . $id . "';");
                $tm = mysql_fetch_array($messages2);
                $thm = $tm[temka];
                if (stristr($thm, "Re:"))
                {
                    $thm = str_replace("Re:", "", $thm);
                    $tema = "Re[1]: $thm";
                } elseif (stristr($thm, "Re["))
                {
                    $t1 = str_replace("Re[", "", $thm);
                    $t1 = strtok($t1, "]");
                    $t1 = $t1 + 1;
                    $o = explode(" ", $thm);
                    $thm = str_replace("$o[0]", "", $thm);
                    $tema = "Re[$t1]:$thm";
                } else
                {
                    $tema = "Re: $thm";
                }
            }
            if (isset($_GET['bir']))
            {
                $tema = "С Днём Рождения!!!";
            }
            echo "Написать письмо<br/>";
            echo "<form action='pradd.php?act=send' method='post' enctype='multipart/form-data'>Для:";
            if (!empty($_GET['adr']))
            {
                echo " $adresat<br/>";
                echo "<input type='hidden' name='foruser' value='" . $adresat . "'/>";
            } else
            {
                echo "<br/><input type='text' name='foruser'/>";
            }
            echo " <br/>Тема:<br/><input type='text' name='tem' value='" . $tema . "'/><br/> Cообщение:<br/><textarea rows='5' name='msg'></textarea><br/>Прикрепить файл(max. $flsz kb):<br/><input type='file' name='fail'/><hr/>Прикрепить файл(Opera Mini):<br/><input name='fail1' value =''/>&nbsp;<br/>
<a href='op:fileselect'>Выбрать файл</a><hr/>";

            if ($offtr != 1)
            {
                echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения<br/>";
            }
            echo "<input type='hidden' name='idm' value='" . $id . "'/><input type='submit' value='Отправить' /></form>";
            echo "<a href='pradd.php?act=trans'>Транслит</a><br/><a href='smile.php'>Смайлы</a><br/>";
            break;

        case "delch":
            require_once ("../incfiles/head.php");
            if (isset($_GET['yes']))
            {
                $dc = $_SESSION['dc'];
                $prd = $_SESSION['prd'];
                foreach ($dc as $delid)
                {
                    mysql_query("delete from `privat` where `id`='" . intval($delid) . "';");
                }
                echo "Отмеченные письма удалены<br/><a href='" . $prd . "'>Назад</a><br/>";
            } else
            {
                if (empty($_POST['delch']))
                {
                    echo "Вы не выбрали писем для удаления<br/><a href='pradd.php?act=in'>Назад</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                foreach ($_POST['delch'] as $v)
                {
                    $dc[] = intval(check($v));
                }

                $_SESSION['dc'] = $dc;
                $_SESSION['prd'] = htmlspecialchars(getenv("HTTP_REFERER"));
                echo "Вы уверены в удалении писем?<br/><a href='pradd.php?act=delch&amp;yes'>Да</a> | <a href='" . htmlspecialchars(getenv("HTTP_REFERER")) . "'>Нет</a><br/>";
            }
            break;

        case "in":
            $headmod = 'pradd';
			require_once ("../incfiles/head.php");
            if (isset($_GET['new']))
            {
                $_SESSION['refpr'] = htmlspecialchars(getenv("HTTP_REFERER"));
                $messages = mysql_query("select * from `privat` where user='" . $login . "' and type='in' and chit='no' order by time desc;");
                echo "Новые входящие<br/>";
            } else
            {
                $messages = mysql_query("select * from `privat` where user='" . $login . "' and type='in' order by time desc;");
                echo "<div style='text-align: center'>Входящие</div>";
            }
            echo "<form action='pradd.php?act=delch' method='post'>";
            $count = mysql_num_rows($messages);
            if (empty($_GET['page']))
            {
                $page = 1;
            } else
            {
                $page = intval($_GET['page']);
            }
            $start = $page * $kmess - $kmess;
            if ($count < $start + $kmess)
            {
                $end = $count;
            } else
            {
                $end = $start + $kmess;
            }
            while ($massiv = mysql_fetch_array($messages))
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
                    $mas = mysql_fetch_array(@mysql_query("select * from `users` where `name`='" . $massiv[author] . "';"));
                    echo "$div<input type='checkbox' name='delch[]' value='" . $massiv[id] . "'/><a href='pradd.php?id=" . $massiv[id] . "&amp;act=readmess'>От $massiv[author]</a>";
                    $vrp = $massiv[time] + $sdvig * 3600;
                    echo "(" . date("d.m.y H:i", $vrp) . ")<br/>Тема: $massiv[temka]<br/>";
                    if (!empty($massiv[attach]))
                    {
                        echo "+ вложение<br/>";
                    }
                    if ($massiv[chit] == "no")
                    {
                        echo "Не прочитано<br/>";
                    }
                    if ($massiv[otvet] == 0)
                    {
                        echo "Не отвечено<br/>";
                    }
                    echo '</div>';
                }
                ++$i;
            }
            if ($count > $kmess)
            {
                echo "<hr/>";

                $ba = ceil($count / $kmess);
                if ($offpg != 1)
                {
                    echo "Страницы:<br/>";
                } else
                {
                    echo "Страниц: $ba<br/>";
                }
                $asd = $start - ($kmess);
                $asd2 = $start + ($kmess * 2);

                if ($start != 0)
                {
                    echo '<a href="pradd.php?act=in&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
                }
                if ($offpg != 1)
                {
                    if ($asd < $count && $asd > 0)
                    {
                        echo ' <a href="pradd.php?act=in&amp;page=1&amp;">1</a> .. ';
                    }
                    $page2 = $ba - $page;
                    $pa = ceil($page / 2);
                    $paa = ceil($page / 3);
                    $pa2 = $page + floor($page2 / 2);
                    $paa2 = $page + floor($page2 / 3);
                    $paa3 = $page + (floor($page2 / 3) * 2);
                    if ($page > 13)
                    {
                        echo ' <a href="pradd.php?act=in&amp;page=' . $paa . '">' . $paa . '</a> <a href="pradd.php?act=in&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="pradd.php?act=in&amp;page=' . ($paa * 2) . '">' . ($paa * 2) .
                            '</a> <a href="pradd.php?act=in&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
                    } elseif ($page > 7)
                    {
                        echo ' <a href="pradd.php?act=in&amp;page=' . $pa . '">' . $pa . '</a> <a href="pradd.php?act=in&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
                    }
                    for ($i = $asd; $i < $asd2; )
                    {
                        if ($i < $count && $i >= 0)
                        {
                            $ii = floor(1 + $i / $kmess);

                            if ($start == $i)
                            {
                                echo " <b>$ii</b>";
                            } else
                            {
                                echo ' <a href="pradd.php?act=in&amp;page=' . $ii . '">' . $ii . '</a> ';
                            }
                        }
                        $i = $i + $kmess;
                    }
                    if ($page2 > 12)
                    {
                        echo ' .. <a href="pradd.php?act=in&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="pradd.php?act=in&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="pradd.php?act=in&amp;page=' . ($paa3) . '">' . ($paa3) .
                            '</a> <a href="pradd.php?act=in&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
                    } elseif ($page2 > 6)
                    {
                        echo ' .. <a href="pradd.php?act=in&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="pradd.php?act=in&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
                    }
                    if ($asd2 < $count)
                    {
                        echo ' .. <a href="pradd.php?act=in&amp;page=' . $ba . '">' . $ba . '</a>';
                    }
                } else
                {
                    echo "<b>[$page]</b>";
                }
                if ($count > $start + $kmess)
                {
                    echo ' <a href="pradd.php?act=in&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
                }
                echo "<form action='pradd.php'>Перейти к странице:<br/><input type='hidden' name='act' value='in'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
            }
            echo "Всего: $count<br/>";
            if ($count > 0)
            {
                echo "<input type='submit' value='Удалить отмеченные'/><br/>";
            }
            echo "</form>";
            if ($count > 0)
            {
                echo "<a href='pradd.php?act=delread'>Удалить прочитанные</a><br/>";
                echo "<a href='pradd.php?act=delin'>Удалить все входящие</a><br/>";
            }
            break;

        case "delread":
            require_once ("../incfiles/head.php");
            $mess1 = mysql_query("select * from `privat` where user='" . $login . "' and type='in' and chit='yes';");
            while ($mas1 = mysql_fetch_array($mess1))
            {
                $delid = $mas1[id];
                $delfile = $mas1[attach];
                if (!empty($delfile))
                {
                    if (file_exists("../pratt/$delfile"))
                    {
                        unlink("../pratt/$delfile");
                    }
                }
                mysql_query("delete from `privat` where `id`='" . intval($delid) . "';");
            }
            echo "Прочитанные письма удалены<br/>";
            break;

        case "delin":
            require_once ("../incfiles/head.php");
            $mess1 = mysql_query("select * from `privat` where user='" . $login . "' and type='in';");
            while ($mas1 = mysql_fetch_array($mess1))
            {
                $delid = $mas1[id];
                $delfile = $mas1[attach];
                if (!empty($delfile))
                {
                    if (file_exists("../pratt/$delfile"))
                    {
                        unlink("../pratt/$delfile");
                    }
                }
                mysql_query("delete from `privat` where `id`='" . intval($delid) . "';");
            }
            echo "Входящие письма удалены<br/>";
            break;

        case "readmess":
            require_once ("../incfiles/head.php");
            $id = intval(check($_GET['id']));
            $messages1 = mysql_query("select * from `privat` where user='" . $login . "' and type='in' and id='" . $id . "';");
            $massiv1 = mysql_fetch_array($messages1);
            if ($massiv1[chit] == "no")
            {
                mysql_query("update `privat` set `chit`='yes' where `id`='" . $massiv1['id'] . "';");
            }
            $newl = mysql_query("select * from `privat` where user = '" . $login . "' and type = 'in' and chit = 'no';");
            $countnew = mysql_num_rows($newl);
            if ($countnew > 0)
            {
                echo "<div style='text-align: center'><a href='$home/str/pradd.php?act=in&amp;new'><b><font color='red'>Вам письмо: $countnew</font></b></a></div>";
            }
            $mass = mysql_fetch_array(@mysql_query("select * from `users` where `name`='" . $massiv1[author] . "';"));
            $massiv1[text] = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $massiv1[text]);
            $massiv1[text] = eregi_replace("\\[l\\]([[:alnum:]_=:/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='http://\\1'>\\6</a>", $massiv1[text]);

            if (stristr($massiv1[text], "<a href="))
            {
                $massiv1[text] = eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)</a>",
                    "<a href='\\1\\3'>\\3</a>", $massiv1[text]);
            } else
            {
                $massiv1[text] = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)", "<a href='\\1\\3'>\\3</a>", $massiv1[text]);
            }
            if ($offsm != 1 && $offgr != 1)
            {
                $tekst = smiles($massiv1[text]);
                $tekst = smilescat($tekst);

                if ($massiv1[from] == nickadmina || $massiv1[from] == nickadmina2 || $massiv11[rights] >= 1)
                {
                    $tekst = smilesadm($tekst);
                }
            } else
            {
                $tekst = $massiv1[text];
            }
            echo "От <a href='anketa.php?user=" . $mass[id] . "'>$massiv1[author]</a><br/>";
            $vrp = $massiv1[time] + $sdvig * 3600;
            echo "(" . date("d.m.y H:i", $vrp) . ")<br/><div class='b'>Тема: $massiv1[temka]<br/></div><div class='c'>Текст: $tekst</div>";
            if (!empty($massiv1[attach]))
            {
                echo "Прикреплённый файл: <a href='?act=load&amp;id=" . $id . "'>$massiv1[attach]</a><br/>";
            }
            echo "<a href='pradd.php?act=write&amp;adr=" . $mass[id] . "&amp;id=" . $massiv1[id] . "'>Ответить</a><br/><a href='pradd.php?act=delmess&amp;del=" . $massiv1[id] . "'>Удалить</a>";
            $mas2 = mysql_fetch_array(@mysql_query("select * from `privat` where `time`='$massiv1[time]' and author='$massiv1[author]' and type='out';"));
            if ($mas2[chit] == "no")
            {
                mysql_query("update `privat` set `chit`='yes' where `id`='" . $mas2['id'] . "';");
            }
            if ($massiv1[chit] == "no")
            {
                mysql_query("update `privat` set `chit`='yes' where `id`='" . $massiv1['id'] . "';");
            }
            break;

        case "delmess":
            require_once ("../incfiles/head.php");
            $mess1 = mysql_query("select * from `privat` where id='" . intval($_GET['del']) . "' and type='in';");
            $mas1 = mysql_fetch_array($mess1);
            $delfile = $mas1[attach];
            if (!empty($delfile))
            {
                if (file_exists("../pratt/$delfile"))
                {
                    unlink("../pratt/$delfile");
                }
            }
            mysql_query("delete from `privat` where `id`='" . intval($_GET['del']) . "';");
            echo "Сообщение удалено!<br/>";
            break;

        case "delout":
            require_once ("../incfiles/head.php");
            $mess1 = mysql_query("select * from `privat` where author='$login' and type='out';");
            while ($mas1 = mysql_fetch_array($mess1))
            {
                $delid = $mas1['id'];
                mysql_query("delete from `privat` where `id`='" . intval($delid) . "';");
            }
            echo "Исходящие письма удалены<br/>";
            break;

        case "out":
            require_once ("../incfiles/head.php");
            $messages = mysql_query("select * from `privat` where author='" . $login . "' and type='out' order by time desc;");
            echo "Исходящие<br/>";
            echo "<form action='pradd.php?act=delch' method='post'>";
            $count = mysql_num_rows($messages);
            if (empty($_GET['page']))
            {
                $page = 1;
            } else
            {
                $page = intval($_GET['page']);
            }
            $start = $page * $kmess - $kmess;
            if ($count < $start + $kmess)
            {
                $end = $count;
            } else
            {
                $end = $start + $kmess;
            }

            while ($massiv = mysql_fetch_array($messages))
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
                    $vpr = $massiv[time] + $sdvig * 3600;
                    echo "$div<input type='checkbox' name='delch[]' value='" . $massiv[id] . "'/><a href='pradd.php?act=readout&amp;id=" . $massiv[id] . "'>Для: $massiv[user]</a> (" . date("d.m.y H:i", $vpr) . ")<br/>Тема: $massiv[temka]<br/>";
                    if (!empty($massiv[attach]))
                    {
                        echo "+ вложение<br/>";
                    }
                    if ($massiv[chit] == "no")
                    {
                        echo "Не прочитано<br/>";
                    }
                    echo "<a href='pradd.php?act=delmess&amp;del=" . $massiv[id] . "'>Удалить</a></div>";
                }
                ++$i;
            }
            if ($count > $kmess)
            {
                echo "<hr/>";

                $ba = ceil($count / $kmess);
                if ($offpg != 1)
                {
                    echo "Страницы:<br/>";
                } else
                {
                    echo "Страниц: $ba<br/>";
                }
                $asd = $start - ($kmess);
                $asd2 = $start + ($kmess * 2);

                if ($start != 0)
                {
                    echo '<a href="pradd.php?act=out&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
                }
                if ($offpg != 1)
                {
                    if ($asd < $count && $asd > 0)
                    {
                        echo ' <a href="pradd.php?act=out&amp;page=1&amp;">1</a> .. ';
                    }
                    $page2 = $ba - $page;
                    $pa = ceil($page / 2);
                    $paa = ceil($page / 3);
                    $pa2 = $page + floor($page2 / 2);
                    $paa2 = $page + floor($page2 / 3);
                    $paa3 = $page + (floor($page2 / 3) * 2);
                    if ($page > 13)
                    {
                        echo ' <a href="pradd.php?act=out&amp;page=' . $paa . '">' . $paa . '</a> <a href="pradd.php?act=out&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="pradd.php?act=out&amp;page=' . ($paa * 2) . '">' . ($paa * 2) .
                            '</a> <a href="pradd.php?act=out&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
                    } elseif ($page > 7)
                    {
                        echo ' <a href="pradd.php?act=out&amp;page=' . $pa . '">' . $pa . '</a> <a href="pradd.php?act=out&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
                    }
                    for ($i = $asd; $i < $asd2; )
                    {
                        if ($i < $count && $i >= 0)
                        {
                            $ii = floor(1 + $i / $kmess);

                            if ($start == $i)
                            {
                                echo " <b>$ii</b>";
                            } else
                            {
                                echo ' <a href="pradd.php?act=out&amp;page=' . $ii . '">' . $ii . '</a> ';
                            }
                        }
                        $i = $i + $kmess;
                    }
                    if ($page2 > 12)
                    {
                        echo ' .. <a href="pradd.php?act=out&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="pradd.php?act=out&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="pradd.php?act=out&amp;page=' . ($paa3) . '">' . ($paa3) .
                            '</a> <a href="pradd.php?act=out&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
                    } elseif ($page2 > 6)
                    {
                        echo ' .. <a href="pradd.php?act=out&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="pradd.php?act=out&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
                    }
                    if ($asd2 < $count)
                    {
                        echo ' .. <a href="pradd.php?act=out&amp;page=' . $ba . '">' . $ba . '</a>';
                    }
                } else
                {
                    echo "<b>[$page]</b>";
                }
                if ($count > $start + $kmess)
                {
                    echo ' <a href="pradd.php?act=out&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
                }
                echo "<form action='pradd.php'>Перейти к странице:<br/><input type='hidden' name='act' value='out'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
            }
            echo "Всего: $count<br/>";
            if ($count > 0)
            {
                echo "<input type='submit' value='Удалить отмеченные'/><br/>";
            }
            echo "</form>";
            if ($count > 0)
            {
                echo "<a href='pradd.php?act=delout'>Удалить все исходящие</a><br/>";
            }
            break;

        case "readout":
            require_once ("../incfiles/head.php");
            $id = intval(check($_GET['id']));
            $messages1 = mysql_query("select * from `privat` where author='" . $login . "' and type='out' and id='" . $id . "';");
            $massiv1 = mysql_fetch_array($messages1);
            $mass = mysql_fetch_array(@mysql_query("select * from `users` where `name`='$massiv1[user]';"));
            $massiv1[text] = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $massiv1[text]);
            $massiv1[text] = eregi_replace("\\[l\\]([[:alnum:]_=:/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='http://\\1'>\\6</a>", $massiv1[text]);
            if (stristr($massiv1[text], "<a href="))
            {
                $massiv1[text] = eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)</a>",
                    "<a href='\\1\\3'>\\3</a>", $massiv1[text]);
            } else
            {
                $massiv1[text] = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)", "<a href='\\1\\3'>\\3</a>", $massiv1[text]);
            }
            if ($offsm != 1 && $offgr != 1)
            {
                $tekst = smiles($massiv1[text]);
                $tekst = smilescat($tekst);

                if ($massiv1[from] == nickadmina || $massiv1[from] == nickadmina2 || $massiv11[rights] >= 1)
                {
                    $tekst = smilesadm($tekst);
                }
            } else
            {
                $tekst = $massiv1[text];
            }
            echo "Для <a href='anketa.php?user=" . $mass[id] . "'>$massiv1[user]</a><br/>";
            $vrp = $massiv1[time] + $sdvig * 3600;
            echo "(" . date("d.m.y H:i", $vrp) . ")<br/><div class='b'>Тема: $massiv1[temka]<br/></div><div class='c'>Текст: $tekst</div>";
            if (!empty($massiv1[attach]))
            {
                echo "Прикреплённый файл: $massiv1[attach]<br/>";
            }
            echo "<a href='pradd.php?act=delmess&amp;del=" . $massiv1[id] . "'>Удалить</a>";
            break;
        case "trans":
            require_once ("../incfiles/head.php");
            include ("../pages/trans.$ras_pages");
            echo '<br/><br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a><br/>';
            break;
    }
    echo "<br/><a href='privat.php'>В письма</a><br/>";
    echo "<a href='pradd.php?act=write'>Написать</a><br/>";
}

require_once ('../incfiles/end.php');

?>