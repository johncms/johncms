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
session_name("SESID");
session_start();
$textl = 'Чат';
require_once ("../incfiles/core.php");

if ($dostsmod == 1)
{
    if (!empty($_GET['act']))
    {
        $act = check($_GET['act']);
    }
    switch ($act)
    {
        case "del":
            if (empty($_GET['id']))
            {
                require_once ("../incfiles/head.php");
                echo "Ошибка!<br/><a href='chat.php?'>В управление чатом</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));
            $typ = mysql_query("select * from `chat` where id='" . $id . "';");
            $ms = mysql_fetch_array($typ);
            if ($ms[type] != "r")
            {
                require_once ("../incfiles/head.php");
                echo "Ошибка!<br/><a href='chat.php?'>В управление чатом</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            switch ($ms[type])
            {
                case "r":
                    if (isset($_GET['yes']))
                    {
                        $mes = mysql_query("select * from `chat` where refid='" . $id . "';");
                        while ($mes1 = mysql_fetch_array($mes))
                        {

                            mysql_query("delete from `chat` where `id`='" . $mes1[id] . "';");
                        }
                        mysql_query("delete from `chat` where `id`='" . $id . "';");
                        header("Location: chat.php");
                    } else
                    {
                        require_once ("../incfiles/head.php");
                        echo "Вы уверены,что хотите удалить комнату $ms[text]?<br/><a href='chat.php?act=del&amp;id=" . $id . "&amp;yes'>Да</a>|<a href='chat.php'>Нет</a><br/>";
                    }

                    break;
                default:
                    require_once ("../incfiles/head.php");
                    echo "Ошибка!<br/><a href='chat.php?'>В управление чатом</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                    break;
            }

            break;

        case "crroom":
            if (isset($_POST['submit']))
            {
                if ((empty($_POST['tr'])) && (empty($_POST['nr'])))
                {
                    require_once ("../incfiles/head.php");
                    echo "Вы не ввели имя комнаты!<br/><a href='chat.php?act=crroom'>Повторить</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                $nr = check($_POST['nr']);
                $tr = check($_POST['tr']);
                if ($tr == "vik")
                {
                    $nr = "Викторина";
                }
                if ($tr == "in")
                {
                    $nr = "Интим";
                }
                $q = mysql_query("select * from `chat` where type='r' order by realid desc;");
                $q1 = mysql_num_rows($q);
                if ($q1 == 0)
                {
                    $rid = 1;
                } else
                {
                    while ($arr = mysql_fetch_array($q))
                    {
                        $arr1[] = $arr[realid];
                    }
                    $rid = $arr1[0] + 1;
                }
                mysql_query("insert into `chat` values(0,'','" . $rid . "','r','" . $realtime . "','','','" . $tr . "','" . $nr . "','','','','');");
                header("Location: chat.php");
            } else
            {
                require_once ("../incfiles/head.php");
                echo "Добавление комнаты:<br/><form action='chat.php?act=crroom' method='post'>Тип комнаты<br/><select name='tr'><option value=''>простая</option>";
                $v = mysql_query("select * from `chat` where type='r' and dpar='vik';");
                $v1 = mysql_num_rows($v);
                $a = mysql_query("select * from `chat` where type='r' and dpar='in';");
                $a1 = mysql_num_rows($a);
                if ($v1 == 0)
                {
                    echo "<option value='vik'>викторина</option>";
                }
                if ($a1 == 0)
                {
                    echo "<option value='in'>интим</option>";
                }
                echo "</select><br/>Название(если простая):<br/><input type='text' name='nr'/><br/><input type='submit' name='submit' value='Ok!'/><br/></form>";
                echo "<a href='chat.php?'>В управление чатом</a><br/>";
            }
            break;

        case "edit":
            if (empty($_GET['id']))
            {
                require_once ("../incfiles/head.php");
                echo "Ошибка!<br/><a href='chat.php?'>В управление чатом</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));
            $typ = mysql_query("select * from `chat` where id='" . $id . "';");
            $ms = mysql_fetch_array($typ);
            if ($ms[type] != "r")
            {
                require_once ("../incfiles/head.php");
                echo "Ошибка!<br/><a href='chat.php?'>В управление чатом</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            if (isset($_POST['submit']))
            {
                if ((empty($_POST['tr'])) && ((empty($_POST['nr'])) || $_POST['nr'] == "Викторина" || $_POST['nr'] == "Интим"))
                {
                    require_once ("../incfiles/head.php");
                    echo "Вы не ввели новое название!<br/><a href='chat.php?act=edit&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                $nr = check(trim($_POST['nr']));
                $tr = check(trim($_POST['tr']));
                if ($tr == "vik")
                {
                    $nr = "Викторина";
                }
                if ($tr == "in")
                {
                    $nr = "Интим";
                }
                mysql_query("update `chat` set  dpar='" . $tr . "',text='" . $nr . "' where id='" . $id . "';");
                header("Location: chat.php");
            } else
            {
                require_once ("../incfiles/head.php");
                echo "Изменить комнату<br/><form action='chat.php?act=edit&amp;id=" . $id . "' method='post'>Тип комнаты<br/><select name='tr'>";
                $v = mysql_query("select * from `chat` where type='r' and dpar='vik';");
                $v1 = mysql_num_rows($v);
                $a = mysql_query("select * from `chat` where type='r' and dpar='in';");
                $a1 = mysql_num_rows($a);
                if (empty($ms[dpar]))
                {
                    echo "<option value=''>простая</option>";
                    if ($v1 == 0)
                    {
                        echo "<option value='vik'>викторина</option>";
                    }
                    if ($a1 == 0)
                    {
                        echo "<option value='in'>интим</option>";
                    }
                }

                if ($ms[dpar] == "vik")
                {
                    echo "<option value='vik'>викторина</option><option value=''>простая</option>";
                    if ($a1 == 0)
                    {
                        echo "<option value='in'>интим</option>";
                    }
                }

                if ($ms[dpar] == "in")
                {
                    echo "<option value='in'>интим</option><option value=''>простая</option>";
                    if ($v1 == 0)
                    {
                        echo "<option value='vik'>викторина</option>";
                    }
                }
                echo "</select><br/>Изменить название(если простая):<br/><input type='text' name='nr' value='" . $ms[text] . "'/><br/><input type='submit' name='submit' value='Ok!'/><br/></form>";
            }
            echo "<a href='chat.php?'>В управление чатом</a><br/>";
            break;

        case "up":
            if (empty($_GET['id']))
            {
                require_once ("../incfiles/head.php");
                echo "Ошибка!<br/><a href='chat.php?'>В управление чатом</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));
            $typ = mysql_query("select * from `chat` where id='" . $id . "';");
            $ms = mysql_fetch_array($typ);
            if ($ms[type] != "r")
            {
                require_once ("../incfiles/head.php");
                echo "Ошибка!<br/><a href='chat.php?'>В управление чатом</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $ri = mysql_query("select * from `chat` where type='r' and realid<'" . $ms[realid] . "' order by realid desc;");
            $rei = mysql_num_rows($ri);
            if ($rei == 0)
            {
                require_once ("../incfiles/head.php");
                echo "Нельзя туда двигать!<br/><a href='chat.php?'>В управление чатом</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            while ($rid = mysql_fetch_array($ri))
            {
                $arr[] = $rid[id];
            }
            $tr = mysql_query("select * from `chat` where type='r' and id='" . $arr[0] . "';");
            $tr1 = mysql_fetch_array($tr);
            $real1 = $tr1[realid];
            $real2 = $ms[realid];
            mysql_query("update `chat` set  realid='" . $real1 . "' where id='" . $id . "';");
            mysql_query("update `chat` set  realid='" . $real2 . "' where id='" . $arr[0] . "';");
            header("Location: chat.php");
            break;

        case "down":

            if (empty($_GET['id']))
            {
                require_once ("../incfiles/head.php");
                echo "Ошибка!<br/><a href='chat.php?'>В управление чатом</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));
            $typ = mysql_query("select * from `chat` where id='" . $id . "';");
            $ms = mysql_fetch_array($typ);
            if ($ms[type] != "r")
            {
                require_once ("../incfiles/head.php");
                echo "Ошибка!<br/><a href='chat.php?'>В управление чатом</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $ri = mysql_query("select * from `chat` where type='r' and realid>'" . $ms[realid] . "' order by realid ;");
            $rei = mysql_num_rows($ri);
            if ($rei == 0)
            {
                require_once ("../incfiles/head.php");
                echo "Нельзя туда двигать!<br/><a href='chat.php?'>В управление чатом</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            while ($rid = mysql_fetch_array($ri))
            {
                $arr[] = $rid[id];
            }
            $tr = mysql_query("select * from `chat` where type='r' and id='" . $arr[0] . "';");
            $tr1 = mysql_fetch_array($tr);
            $real1 = $tr1[realid];
            $real2 = $ms[realid];
            mysql_query("update `chat` set  realid='" . $real1 . "' where id='" . $id . "';");
            mysql_query("update `chat` set  realid='" . $real2 . "' where id='" . $arr[0] . "';");
            header("Location: chat.php");
            break;

        default:
            require_once ("../incfiles/head.php");
            $q = mysql_query("select * from `chat` where type='r' order by realid ;");
            while ($mass = mysql_fetch_array($q))
            {
                $d = $i / 2;
                $d1 = ceil($d);
                $d2 = $d1 - $d;
                $d3 = ceil($d2);
                if ($d3 == 0)
                {
                    $div = "<div class='b'>";
                } else
                {
                    $div = "<div class='c'>";
                }
                $ri = mysql_query("select * from `chat` where type='r' and  realid>'" . $mass[realid] . "';");
                $rei = mysql_num_rows($ri);
                $ri1 = mysql_query("select * from `chat` where type='r' and realid<'" . $mass[realid] . "';");
                $rei1 = mysql_num_rows($ri1);
                echo "$div$mass[text]<br/>";
                if ($rei1 != 0)
                {
                    echo "<a href='chat.php?act=up&amp;id=" . $mass[id] . "'>Вверх</a> | ";
                }
                if ($rei != 0)
                {
                    echo "<a href='chat.php?act=down&amp;id=" . $mass[id] . "'>Вниз</a> | ";
                }
                echo "<a href='chat.php?act=edit&amp;id=" . $mass[id] . "'>Edit</a> | <a href='chat.php?act=del&amp;id=" . $mass[id] . "'>Del</a>";
                echo "</div>";
                ++$i;
            }
            echo "<hr/><a href='chat.php?act=crroom'>Создать комнату</a><br/><br/>";
            echo "<a href='../chat/index.php'>В чат</a><br/>";
            break;
    }
} else
{
    header("Location: ../index.php?err");
}
require_once ("../incfiles/end.php");
?>