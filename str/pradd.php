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
$textl = 'Почта(письма)';
require_once ("../incfiles/core.php");


if ($user_id)
{
    $msg = check(trim($_POST['msg']));
    if ($_POST['msgtrans'] == 1)
    {
        $msg = trans($msg);
    }
    $foruser = check(trim($_POST['foruser']));
    $tem = check(trim($_POST['tem']));
    $idm = intval($_POST['idm']);

    $act = isset($_GET['act']) ? $_GET['act'] : '';
    switch ($act)
    {
        case 'send':
            ////////////////////////////////////////////////////////////
            // Отправка письма и обработка прикрепленного файла       //
            ////////////////////////////////////////////////////////////

            // Проверка на спам
            $old = ($rights > 0 || $dostsadm = 1) ? 10:
            30;
            if ($lastpost > ($realtime - $old))
            {
                require_once ("../incfiles/head.php");
                echo "<p><b>Антифлуд!</b><br />Вы не можете так часто писать<br/>Порог $old секунд<br/><br/><a href='privat.php'>Назад</a></p>";
                require_once ("../incfiles/end.php");
                exit;
            }

            if ($ban['1'] || $ban['3'])
                exit;
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
                    $adres = $us['id'];

                    ////////////////////////////////////////////////////////////
                    // Проверка, был ли выгружен файл и с какого браузера     //
                    ////////////////////////////////////////////////////////////
                    $do_file = false;
                    $do_file_mini = false;
                    // Проверка загрузки с обычного браузера
                    if ($_FILES['fail']['size'] > 0)
                    {
                        $do_file = true;
                        $fname = strtolower($_FILES['fail']['name']);
                        $fsize = $_FILES['fail']['size'];
                    }
                    // Проверка загрузки с Opera Mini
                    elseif (strlen($_POST['fail1']) > 0)
                    {
                        $do_file_mini = true;
                        $array = explode('file=', $_POST['fail1']);
                        $fname = strtolower($array[0]);
                        $filebase64 = $array[1];
                        $fsize = strlen(base64_decode($filebase64));
                    }

                    ////////////////////////////////////////////////////////////
                    // Обработка файла (если есть)                            //
                    ////////////////////////////////////////////////////////////
                    if ($do_file || $do_file_mini)
                    {
                        // Список допустимых расширений файлов.
                        $al_ext = array('rar', 'zip', 'pdf', 'txt', 'tar', 'gz', 'jpg', 'jpeg', 'gif', 'png', 'bmp', '3gp', 'mp3', 'mpg', 'sis', 'thm', 'jar', 'jad', 'cab', 'sis', 'sisx', 'exe', 'msi');
                        $ext = explode(".", $fname);
                        // Проверка на допустимый размер файла
                        if ($fsize >= 1024 * $flsz)
                        {
                            echo '<p><b>ОШИБКА!</b></p><p>Вес файла превышает ' . $flsz . ' кб.';
                            echo '</p><p><a href="pradd.php?act=write&amp;adr=' . $adres . '">Повторить</a></p>';
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        // Проверка файла на наличие только одного расширения
                        if (count($ext) != 2)
                        {
                            echo '<p><b>ОШИБКА!</b></p><p>Неправильное имя файла!<br />';
                            echo 'К отправке разрешены только файлы имеющие имя и одно расширение (<b>name.ext</b>).<br />';
                            echo 'Запрещены файлы не имеющие имени, расширения, или с двойным расширением.';
                            echo '</p><p><a href="pradd.php?act=write&amp;adr=' . $adres . '">Повторить</a></p>';
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        // Проверка допустимых расширений файлов
                        if (!in_array($ext[1], $al_ext))
                        {
                            echo '<p><b>ОШИБКА!</b></p><p>Запрещенный тип файла!<br />';
                            echo 'К отправке разрешены только файлы, имеющие следующее расширение:<br />';
                            echo implode(', ', $al_ext);
                            echo '</p><p><a href="pradd.php?act=write&amp;adr=' . $adres . '">Повторить</a></p>';
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        // Проверка на длину имени
                        if (strlen($fname) > 30)
                        {
                            echo '<p><b>ОШИБКА!</b></p><p>Длина названия файла не должна превышать 30 символов!';
                            echo '</p><p><a href="pradd.php?act=write&amp;adr=' . $adres . '">Повторить</a></p>';
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        // Проверка на запрещенные символы
                        if (eregi("[^a-z0-9.()+_-]", $fname))
                        {
                            echo '<p><b>ОШИБКА!</b></p><p>В названии файла "<b>' . $fname . '</b>" присутствуют недопустимые символы.<br />';
                            echo 'Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br />Запрещены пробелы.';
                            echo '</p><p><a href="pradd.php?act=write&amp;adr=' . $adres . '">Повторить</a></p>';
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        // Проверка наличия файла с таким же именем
                        if (file_exists("../pratt/$fname"))
                        {
                            $fname = $realtime . $fname;
                        }
                        // Окончательная обработка
                        if ($do_file)
                        {
                            // Для обычного браузера
                            if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "../pratt/$fname")) == true)
                            {
                                @chmod("$fname", 0777);
                                @chmod("../pratt/$fname", 0777);
                                echo 'Файл прикреплен!<br/>';
                            } else
                            {
                                echo 'Ошибка прикрепления файла.<br/>';
                            }
                        } elseif ($do_file_mini)
                        {
                            // Для Opera Mini
                            if (strlen($filebase64) > 0)
                            {
                                $FileName = "../pratt/$fname";
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
                                    echo 'Файл прикреплён.<br/>';
                                } else
                                {
                                    echo 'Ошибка прикрепления файла.<br/>';
                                }
                            }
                        }
                    }
                    mysql_query("insert into `privat` values(0,'" . $foruser . "','" . $msg . "','" . $realtime . "','" . $login . "','in','no','" . $tem . "','0','','','','" . mysql_real_escape_string($fname) . "');");
                    mysql_query("insert into `privat` values(0,'" . $foruser . "','" . $msg . "','" . $realtime . "','" . $login . "','out','no','" . $tem . "','0','','','','" . mysql_real_escape_string($fname) . "');");
                    if (!empty($idm))
                    {
                        mysql_query("update `privat` set otvet='1' where id='" . $idm . "';");
                    }
                    mysql_query("UPDATE `users` SET `lastpost` = '" . $realtime . "' WHERE `id` = '" . $user_id . "'");
                    echo "<p>Письмо отправлено!</p>";
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

        case 'load':
            ////////////////////////////////////////////////////////////
            // Скачивание файла                                       //
            ////////////////////////////////////////////////////////////
            $id = intval($_GET['id']);
            $fil = mysql_query("select * from `privat` where id='" . $id . "';");
            $mas = mysql_fetch_array($fil);
            $att = $mas['attach'];
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

        case 'write':
            ////////////////////////////////////////////////////////////
            // Форма для отправки привата                             //
            ////////////////////////////////////////////////////////////
            if ($ban['1'] || $ban['3'])
                exit;

            // Проверка на спам
            $old = ($rights > 0 || $dostsadm = 1) ? 10:
            30;
            if ($lastpost > ($realtime - $old))
            {
                require_once ("../incfiles/head.php");
                echo "<p><b>Антифлуд!</b><br />Вы не можете так часто писать<br/>Порог $old секунд<br/><br/><a href='privat.php'>Назад</a></p>";
                require_once ("../incfiles/end.php");
                exit;
            }

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
                $id = intval($_GET['id']);
                $messages2 = mysql_query("select * from `privat` where id='" . $id . "';");
                $tm = mysql_fetch_array($messages2);
                $thm = $tm['temka'];
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

        case 'delch':
            ////////////////////////////////////////////////////////////
            // Удаление выбранных писем                               //
            ////////////////////////////////////////////////////////////
            require_once ("../incfiles/head.php");
            if (isset($_GET['yes']))
            {
                $dc = $_SESSION['dc'];
                $prd = $_SESSION['prd'];
                foreach ($dc as $delid)
                {
                    mysql_query("DELETE FROM `privat` WHERE `user` = '" . $login . "' AND `id`='" . intval($delid) . "'");
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
                    $dc[] = intval($v);
                }

                $_SESSION['dc'] = $dc;
                $_SESSION['prd'] = htmlspecialchars(getenv("HTTP_REFERER"));
                echo "Вы уверены в удалении писем?<br/><a href='pradd.php?act=delch&amp;yes'>Да</a> | <a href='" . htmlspecialchars(getenv("HTTP_REFERER")) . "'>Нет</a><br/>";
            }
            break;

        case 'in':
            ////////////////////////////////////////////////////////////
            // Список входящих писем                                  //
            ////////////////////////////////////////////////////////////
            $headmod = 'pradd';
            require_once ("../incfiles/head.php");
            if (isset($_GET['new']))
            {
                $_SESSION['refpr'] = htmlspecialchars(getenv("HTTP_REFERER"));
                $messages = mysql_query("select * from `privat` where user='" . $login . "' and type='in' and chit='no' order by time desc;");
                echo '<div class="phdr">Новые входящие</div>';
            } else
            {
                $messages = mysql_query("select * from `privat` where user='" . $login . "' and type='in' order by time desc;");
                echo '<div class="phdr">Входящие письма</div>';
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
                    $mas = mysql_fetch_array(@mysql_query("select * from `users` where `name`='" . $massiv['author'] . "';"));
                    echo "$div<input type='checkbox' name='delch[]' value='" . $massiv['id'] . "'/><a href='pradd.php?id=" . $massiv['id'] . "&amp;act=readmess'>От $massiv[author]</a>";
                    $vrp = $massiv['time'] + $sdvig * 3600;
                    echo "(" . date("d.m.y H:i", $vrp) . ")<br/>Тема: $massiv[temka]<br/>";
                    if (!empty($massiv['attach']))
                    {
                        echo "+ вложение<br/>";
                    }
                    if ($massiv['chit'] == "no")
                    {
                        echo "Не прочитано<br/>";
                    }
                    if ($massiv['otvet'] == 0)
                    {
                        echo "Не отвечено<br/>";
                    }
                    echo '</div>';
                }
                ++$i;
            }
            echo "<hr/>";
            if ($count > $kmess)
            {
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

        case 'delread':
            ////////////////////////////////////////////////////////////
            // Удаление прочитанных писем                             //
            ////////////////////////////////////////////////////////////
            require_once ("../incfiles/head.php");
            $mess1 = mysql_query("select * from `privat` where user='" . $login . "' and type='in' and chit='yes';");
            while ($mas1 = mysql_fetch_array($mess1))
            {
                $delid = $mas1['id'];
                $delfile = $mas1['attach'];
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

        case 'delin':
            ////////////////////////////////////////////////////////////
            // Удаление всех входящих писем                           //
            ////////////////////////////////////////////////////////////
            require_once ("../incfiles/head.php");
            $mess1 = mysql_query("select * from `privat` where user='" . $login . "' and type='in';");
            while ($mas1 = mysql_fetch_array($mess1))
            {
                $delid = $mas1['id'];
                $delfile = $mas1['attach'];
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

        case 'readmess':
            ////////////////////////////////////////////////////////////
            // Читаем входящие письма                                 //
            ////////////////////////////////////////////////////////////
            require_once ("../incfiles/head.php");
            $id = intval($_GET['id']);
            $messages1 = mysql_query("select * from `privat` where user='" . $login . "' and type='in' and id='" . $id . "';");
            $massiv1 = mysql_fetch_array($messages1);
            if ($massiv1['chit'] == "no")
            {
                mysql_query("update `privat` set `chit`='yes' where `id`='" . $massiv1['id'] . "';");
            }
            $newl = mysql_query("select * from `privat` where user = '" . $login . "' and type = 'in' and chit = 'no';");
            $countnew = mysql_num_rows($newl);
            if ($countnew > 0)
            {
                echo "<div style='text-align: center'><a href='$home/str/pradd.php?act=in&amp;new'><b><font color='red'>Вам письмо: $countnew</font></b></a></div>";
            }
            $mass = mysql_fetch_array(@mysql_query("select * from `users` where `name`='" . $massiv1['author'] . "';"));
            $text = $massiv1['text'];
            $text = tags($text);
            if ($offsm != 1 && $offgr != 1)
            {
                $text = smiles($text);
                $text = smilescat($text);
                if ($massiv1['from'] == nickadmina || $massiv1['from'] == nickadmina2 || $massiv11['rights'] >= 1)
                {
                    $text = smilesadm($text);
                }
            }
            echo "<p>От <a href='anketa.php?user=" . $mass['id'] . "'>$massiv1[author]</a><br/>";
            $vrp = $massiv1['time'] + $sdvig * 3600;
            echo "(" . date("d.m.y H:i", $vrp) . ")</p><p><div class='b'>Тема: $massiv1[temka]<br/></div>Текст: $text</p>";
            if (!empty($massiv1['attach']))
            {
                echo "<p>Прикреплённый файл: <a href='?act=load&amp;id=" . $id . "'>$massiv1[attach]</a></p>";
            }
            echo "<hr /><p><a href='pradd.php?act=write&amp;adr=" . $mass['id'] . "&amp;id=" . $massiv1['id'] . "'>Ответить</a><br/><a href='pradd.php?act=delmess&amp;del=" . $massiv1['id'] . "'>Удалить</a></p>";
            $mas2 = mysql_fetch_array(@mysql_query("select * from `privat` where `time`='$massiv1[time]' and author='$massiv1[author]' and type='out';"));
            if ($mas2['chit'] == "no")
            {
                mysql_query("update `privat` set `chit`='yes' where `id`='" . $mas2['id'] . "';");
            }
            if ($massiv1['chit'] == "no")
            {
                mysql_query("update `privat` set `chit`='yes' where `id`='" . $massiv1['id'] . "';");
            }
            break;

        case 'delmess':
            ////////////////////////////////////////////////////////////
            // Удаление отдельного сообщения                          //
            ////////////////////////////////////////////////////////////
            require_once ("../incfiles/head.php");
            $mess1 = mysql_query("select * from `privat` where id='" . intval($_GET['del']) . "' and type='in';");
            $mas1 = mysql_fetch_array($mess1);
            $delfile = $mas1['attach'];
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

        case 'delout':
            ////////////////////////////////////////////////////////////
            // Удаление отправленных писем                            //
            ////////////////////////////////////////////////////////////
            require_once ("../incfiles/head.php");
            $mess1 = mysql_query("select * from `privat` where author='$login' and type='out';");
            while ($mas1 = mysql_fetch_array($mess1))
            {
                $delid = $mas1['id'];
                mysql_query("delete from `privat` where `id`='" . intval($delid) . "';");
            }
            echo "Исходящие письма удалены<br/>";
            break;

        case 'out':
            ////////////////////////////////////////////////////////////
            // Список отправленных                                    //
            ////////////////////////////////////////////////////////////
            require_once ("../incfiles/head.php");
            $messages = mysql_query("select * from `privat` where author='" . $login . "' and type='out' order by time desc;");
            echo '<div class="phdr">Исходящие письма</div>';
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
                    $vpr = $massiv['time'] + $sdvig * 3600;
                    echo "$div<input type='checkbox' name='delch[]' value='" . $massiv['id'] . "'/><a href='pradd.php?act=readout&amp;id=" . $massiv['id'] . "'>Для: $massiv[user]</a> (" . date("d.m.y H:i", $vpr) . ")<br/>Тема: $massiv[temka]<br/>";
                    if (!empty($massiv['attach']))
                    {
                        echo "+ вложение<br/>";
                    }
                    if ($massiv['chit'] == "no")
                    {
                        echo "Не прочитано<br/>";
                    }
                    echo '</div>';
                }
                ++$i;
            }
            echo '<hr/>';
            if ($count > $kmess)
            {
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

        case 'readout':
            ////////////////////////////////////////////////////////////
            // Читаем исходящие письма                                //
            ////////////////////////////////////////////////////////////
            require_once ("../incfiles/head.php");
            $id = intval($_GET['id']);
            $messages1 = mysql_query("select * from `privat` where author='" . $login . "' and type='out' and id='" . $id . "';");
            $massiv1 = mysql_fetch_array($messages1);
            $mass = mysql_fetch_array(@mysql_query("select * from `users` where `name`='$massiv1[user]';"));
            $text = $massiv1['text'];
            $text = tags($text);
            if ($offsm != 1 && $offgr != 1)
            {
                $text = smiles($text);
                $text = smilescat($text);
                if ($massiv1['from'] == nickadmina || $massiv1['from'] == nickadmina2 || $massiv11['rights'] >= 1)
                {
                    $text = smilesadm($text);
                }
            }
            echo "<p>Для <a href='anketa.php?user=" . $mass['id'] . "'>$massiv1[user]</a><br/>";
            $vrp = $massiv1[time] + $sdvig * 3600;
            echo "(" . date("d.m.y H:i", $vrp) . ")</p><p><div class='b'>Тема: $massiv1[temka]<br/></div>Текст: $text</p>";
            if (!empty($massiv1['attach']))
            {
                echo "<p>Прикреплённый файл: $massiv1[attach]</p>";
            }
            echo "<hr /><p><a href='pradd.php?act=delmess&amp;del=" . $massiv1['id'] . "'>Удалить</a></p>";
            break;
        case 'trans':
            require_once ("../incfiles/head.php");
            include ("../pages/trans.$ras_pages");
            echo '<br/><br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a><br/>';
            break;
    }
    echo "<p><a href='privat.php'>В письма</a><br/>";
    echo "<a href='pradd.php?act=write'>Написать</a></p>";
}

require_once ('../incfiles/end.php');

?>