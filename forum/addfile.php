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

require_once ("../incfiles/head.php");
if (empty($_GET['id']))
{
    echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
$id = intval(check($_GET['id']));
if (empty($_SESSION['uid']))
{
    echo "Вы не авторизованы!<br/>";
    require_once ("../incfiles/end.php");
    exit;
}

$typ = mysql_query("select `id`, `type`, `from`, `refid` from `forum` where `id`= '" . $id . "';");
$ms = mysql_fetch_array($typ);
if ($ms[from] != $login)
{
    echo "Ошибка!<br/>";
    require_once ("../incfiles/end.php");
    exit;
}

switch ($ms[type])
{
    case "m":
        if (isset($_POST['submit']))
        {
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
                    echo '</p><p><a href="index.php?act=addfile&amp;id=' . $id . '">Повторить</a></p>';
                    require_once ('../incfiles/end.php');
                    exit;
                }

                // Проверка файла на наличие только одного расширения
                if (count($ext) != 2)
                {
                    echo '<p><b>ОШИБКА!</b></p><p>Неправильное имя файла!<br />';
                    echo 'К отправке разрешены только файлы имеющие имя и одно расширение (<b>name.ext</b>).<br />';
                    echo 'Запрещены файлы не имеющие имени, расширения, или с двойным расширением.';
                    echo '</p><p><a href="index.php?act=addfile&amp;id=' . $id . '">Повторить</a></p>';
                    require_once ('../incfiles/end.php');
                    exit;
                }

                // Проверка допустимых расширений файлов
                if (!in_array($ext[1], $al_ext))
                {
                    echo '<p><b>ОШИБКА!</b></p><p>Запрещенный тип файла!<br />';
                    echo 'К отправке разрешены только файлы, имеющие следующее расширение:<br />';
                    echo implode(', ', $al_ext);
                    echo '</p><p><a href="index.php?act=addfile&amp;id=' . $id . '">Повторить</a></p>';
                    require_once ('../incfiles/end.php');
                    exit;
                }

                // Проверка на длину имени
                if (strlen($fname) > 30)
                {
                    echo '<p><b>ОШИБКА!</b></p><p>Длина названия файла не должна превышать 30 символов!';
                    echo '</p><p><a href="index.php?act=addfile&amp;id=' . $id . '">Повторить</a></p>';
                    require_once ('../incfiles/end.php');
                    exit;
                }

                // Проверка на запрещенные символы
                if (eregi("[^a-z0-9.()+_-]", $fname))
                {
                    echo '<p><b>ОШИБКА!</b></p><p>В названии файла "<b>' . $fname . '</b>" присутствуют недопустимые символы.<br />';
                    echo 'Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br />Запрещены пробелы.';
                    echo '</p><p><a href="index.php?act=addfile&amp;id=' . $id . '">Повторить</a></p>';
                    require_once ('../incfiles/end.php');
                    exit;
                }

                // Проверка наличия файла с таким же именем
                if (file_exists("files/$fname"))
                {
                    $fname = $realtime . $fname;
                }

                // Окончательная обработка
                if ($do_file)
                {
                    // Для обычного браузера
                    if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "files/$fname")) == true)
                    {
                        @chmod("$fname", 0777);
                        @chmod("files/$fname", 0777);
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
                        $FileName = "files/$fname";
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

            mysql_query("update `forum` set  attach='" . mysql_real_escape_string($fname) . "' where id='" . $id . "';");
            $pa = mysql_query("select `id` from `forum` where type='m' and refid= '" . $ms['refid'] . "';");
            $pa2 = mysql_num_rows($pa);
            if (((empty($_SESSION['uid'])) && (!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1)) || ((!empty($_SESSION['uid'])) && $upfp == 1))
            {
                $page = 1;
            } else
            {
                $page = ceil($pa2 / $kmess);
            }
            echo "<br/><a href='index.php?id=" . $ms[refid] . "&amp;page=" . $page . "'>Продолжить</a><br/>";
        } else
        {
            echo "Добавление файла (max. $flsz kb)<br/><form action='index.php?act=addfile&amp;id=" . $id . "' method='post' enctype='multipart/form-data'>";
            if (!eregi("Opera/8.01", $agent))
            {
                echo "<input type='file' name='fail'/><br/>";
            } else
            {
                echo "	<input name='fail1' value =''/>&nbsp;<br/>
<a href='op:fileselect'>Выбрать файл</a><br/>";
            }
            echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/><br/></form>";
        }
        break;
    default:
        echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";

        break;
}

?>