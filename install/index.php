<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Доп. сайт поддержки:                http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Плагиат и удаление копирайтов заруганы на ближайших родственников!!!       //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_JOHNCMS', 1);
header("Cache-Control: no-cache, must-revalidate");
header("Content-type: application/xhtml+xml; charset=UTF-8");
echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>
<head>
<meta http-equiv='content-type' content='application/xhtml+xml; charset=utf-8'/>";
echo "<link rel='shortcut icon' href='ico.gif' />
<title>Установка системы</title>
<style type='text/css'>
body { font-weight: normal; font-family: Century Gothic; font-size: 12px; color: #FFFFFF; background-color: #000033}
a:link { text-decoration: underline; color : #D3ECFF}
a:active { text-decoration: underline; color : #2F3528 }
a:visited { text-decoration: underline; color : #31F7D4}
a:hover { text-decoration: none; font-size: 12px; color : #E4F992 }
.red { color: #FF0000; font-weight: bold; }
.green{ color: #009933; font-weight: bold; }
.gray{ color: #FF0000; font: small; }
</style>
</head><body>";
echo '<big><b>JohnCMS</b></big><br />Установка системы<hr />';

switch ($_GET['act'])
{
    case 'set':
        ////////////////////////////////////////////////////////////
        // Создание таблиц в базе данных MySQL                    //
        ////////////////////////////////////////////////////////////
        require_once ("../incfiles/db.php");
        require_once ("../incfiles/func.php");
        $connect = mysql_connect($db_host, $db_user, $db_pass) or die('cannot connect to server</body></html>');
        mysql_select_db($db_name) or die('cannot connect to db');
        mysql_query("SET NAMES 'utf8'", $connect);
        $error = '';
        @set_magic_quotes_runtime(0);
        // Читаем SQL файл и заносим его в базу данных
        $query = fread(fopen('data/install.sql', 'r'), filesize('data/install.sql'));
        $pieces = split_sql($query);
        for ($i = 0; $i < count($pieces); $i++)
        {
            $pieces[$i] = trim($pieces[$i]);
            if (!empty($pieces[$i]) && $pieces[$i] != "#")
            {
                if (!mysql_query($pieces[$i]))
                {
                    $error = $error . mysql_error() . '<br />';
                }
            }
        }
        if (empty($error))
        {
            echo '<span class="green">OK</span> - Таблицы созданы<br />';

            // Принимаем данные из формы
            $log = trim($_POST['wnickadmina']);
            $latlog = rus_lat(mb_strtolower($log));
            $par = trim($_POST['wpassadmina']);
            $par1 = md5(md5($par));
            $meil = trim($_POST['wemailadmina']);
            $hom = trim($_POST[whome]);
            $brow = $_SERVER["HTTP_USER_AGENT"];
            $ip = $_SERVER["REMOTE_ADDR"];

            // Настройка администратора
            mysql_query("insert into `users` set
			`name`='" . mysql_real_escape_string($log) . "',
			`name_lat`='" . mysql_real_escape_string($latlog) . "',
			`password`='" . mysql_real_escape_string($par1) . "',
			`sex`='m',
			`datereg`='" . time() . "',
			`lastdate`='" . time() . "',
			`mail`='" . mysql_real_escape_string($meil) . "',
			`www`='" . mysql_real_escape_string($hom) . "',
			`rights`='7',
			`ip`='" . $ip . "',
			`browser`='" . mysql_real_escape_string($brow) . "',
			`preg`='1';") or die('Ошибка настройки администратора</body></html>');
            $user_id = mysql_insert_id();
            echo '<span class="green">OK</span> - администратор настроен<br />';

            // Импорт настроек
            mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($log) . "' WHERE `key`='nickadmina';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($meil) . "' WHERE `key`='emailadmina';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string(trim($_POST['wcopyright'])) . "' WHERE `key`='copyright';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($hom) . "' WHERE `key`='homeurl';");
            echo '<span class="green">OK</span> - настройки импортированы<br />';

            // Импорт вопросов Викторины
            $file = file("data/vopros.txt");
            $count = count($file);
            for ($i = 0; $i < $count; $i++)
            {
                $tx = explode("||", $file[$i]);
                mysql_query("INSERT INTO `vik` SET
				`vopros`='" . mysql_real_escape_string(trim($tx[0])) . "',
				`otvet`='" . mysql_real_escape_string(trim($tx[1])) . "'
				");
            }
            echo '<span class="green">OK</span> - викторина импортирована (' . $i . ')<br />';
            // Установка ДЕМО данных
            echo '<hr /><p>При желании, Вы можете установить ДЕМО данные<br />Это может быть полезно для начинающих сайтостроителей.<br />В базу будут внесены некоторые исходные настроики и материалы.</p>';
            echo '<form method="post" action="index.php?act=demo">';
            echo '<input name="id" type="hidden" value="' . $user_id . '"/>';
            echo '<input name="ps" type="hidden" value="' . $_POST['wpassadmina'] . '"/>';
            echo '<input value="Установить" type="submit"/></form>';
            // Напоминание и ссылка на вход
            echo "<hr/><span class='red'>НЕ ЗАБУДЬТЕ!!!</span><br />1) Сменить права к папке incfiles на 755<br />2) Сменить права на файл incfiles/db.php 644<br />3) Удалить папку install с сайта.<br/>";
            echo "<a href='../auto.php?id=" . $user_id . "&amp;p=" . $_POST['wpassadmina'] . "'>Вход на сайт</a><br/>";
        } else
        {
            // Если были ошибки, выводим их
            echo $error;
            echo '<br /><span class="red">ERROR!!!</span><br />При создании таблиц возникла ошибка.<br />Продолжение невозможно.';
        }
        break;

    case 'demo':
        ////////////////////////////////////////////////////////////
        // Установка ДЕМО данных                                  //
        ////////////////////////////////////////////////////////////
        require_once ("../incfiles/db.php");
        require_once ("../incfiles/func.php");
        $connect = mysql_connect($db_host, $db_user, $db_pass) or die('cannot connect to server</body></html>');
        mysql_select_db($db_name) or die('cannot connect to db');
        mysql_query("SET NAMES 'utf8'", $connect);
        $error = '';
        @set_magic_quotes_runtime(0);
        // Читаем SQL файл и заносим его в базу данных
        $query = fread(fopen('data/demo.sql', 'r'), filesize('data/demo.sql'));
        $pieces = split_sql($query);
        for ($i = 0; $i < count($pieces); $i++)
        {
            $pieces[$i] = trim($pieces[$i]);
            if (!empty($pieces[$i]) && $pieces[$i] != "#")
            {
                if (!mysql_query($pieces[$i]))
                {
                    $error = $error . mysql_error() . '<br />';
                }
            }
        }
        if (empty($error))
        {
            echo '<span class="green">OK</span> - ДЕМО данные установлены<br />';
        } else
        {
            // Если были ошибки, выводим их
            echo $error;
            echo '<br /><span class="red">ERROR!!!</span><br />В процессе установки ДЕМО данных возникли ошибки.';
        }
        echo "<hr/><span class='red'>НЕ ЗАБУДЬТЕ!!!</span><br />1) Сменить права к папке incfiles на 755<br />2) Сменить права на файл incfiles/db.php 644<br />3) Удалить папку install с сайта.<br/>";
        echo "<a href='../auto.php?id=" . $_POST['id'] . "&amp;p=" . $_POST['ps'] . "'>Вход на сайт</a><br/>";
        break;

    case "admin":
        ////////////////////////////////////////////////////////////
        // Настройки сайта и Администратора                       //
        ////////////////////////////////////////////////////////////
        $dhost = trim($_POST['host']);
        $duser = trim($_POST['user']);
        $dpass = trim($_POST['pass']);
        $dname = trim($_POST['name']);
        $text = "<?php\r\n\r\n" . "defined('_IN_JOHNCMS') or die ('Error: restricted access');\r\n\r\n" . "$" . "version_info = 100270408;\r\n\r\n" . "$" . "db_host=\"$dhost\";\r\n" . "$" . "db_user=\"$duser\";\r\n" . "$" . "db_pass=\"$dpass\";\r\n" .
            "$" . "db_name=\"$dname\";\r\n" . "\r\n?>";
        $fp = @fopen("../incfiles/db.php", "w");
        fputs($fp, $text);
        fclose($fp);
        echo 'Установка сайта<br/>';
        echo '<form method="post" action="index.php?act=set">';
        echo 'Ник админа:<br/><input name="wnickadmina" maxlength="50" /><br/>';
        echo 'Пароль админа:<br/><input name="wpassadmina" maxlength="50" /><br/>';
        echo 'е-mail админа:<br/><input name="wemailadmina" maxlength="50" /><br/>';
        echo 'Копирайт:<br/><input name="wcopyright" maxlength="100" /><br/>';
        echo 'Главная сайта без слэша в конце:<br/><input name="whome" maxlength="100" value="http://' . $_SERVER["SERVER_NAME"] . '" /><br/><br/>';
        echo '<input value="Установить" type="submit"/></form>';
        break;

    case 'db':
        ////////////////////////////////////////////////////////////
        // Настройка соединения с MySQL                           //
        ////////////////////////////////////////////////////////////
        echo '<center><b>Настройки соединения</b></center><br/><br/>';
        echo '<form action="index.php?act=admin&amp;" method="post">';
        echo '<div style="background:#003300;color:#CCCCCC;">';
        echo 'Сервер<br/><input type="text" name="host" value="localhost"/><br/>';
        echo 'Имя пользователя<br/><input type="text" name="user" /><br/>';
        echo 'Пароль<br/><input type="password" name="pass" /><br/>';
        echo 'Название базы<br/><input type="text" name="name" /><br/><br/>';
        echo '<input type="submit" value="Ok!"/><br/></div><br/>';
        echo '</form>';
        break;

    default:
        ////////////////////////////////////////////////////////////
        // Предварительная проверка системы                       //
        // 1) Проверка настроек PHP                               //
        // 2) Проверка необходимых расширений PHP                 //
        // 2) Проверка прав доступа к файлам и папкам             //
        ////////////////////////////////////////////////////////////
        $err = false;
        echo '<b>ПРОВЕРКА СИСТЕМЫ</b><br /><br />';

        // Проверка настроек PHP
        echo '<b><u>Настройки PHP</u></b><br />';
        if (version_compare(phpversion(), '4.1.0', '>'))
        {
            echo '<span class="green">OK</span> - Версия PHP ' . phpversion() . '<br />';
        } else
        {
            $err = 1;
            echo '<span class="red">ОШИБКА!</span> - Версия PHP ' . phpversion() . '<br />';
            echo '<span class="gray">Эта версия PHP устаревшая и не поддерживается системой.</span><br />';
        }
        if (!ini_get('register_globals'))
        {
            echo '<span class="green">OK</span> - register_globals OFF<br />';
        } else
        {
            $err = 2;
            echo '<span class="red">ВНИМАНИЕ!</span> - register_globals OFF<br />';
            echo '<span class="gray">Вы можете продолжить установку, однако система в большей степени будет подвержена уязвимостям.</span><br />';
        }
        if (ini_get('arg_separator.output') == '&amp;')
        {
            echo '<span class="green">OK</span> - arg_separator.output "&amp;amp;"<br />';
        } else
        {
            $err = 2;
            echo '<span class="red">ВНИМАНИЕ!</span> - arg_separator.output "' . htmlentities(ini_get('arg_separator.output')) . '"<br />';
            echo '<span class="gray">Вы можете продолжить установку, однако настоятельно рекомендуется установить этот параметр на "&amp;amp;",<br /> иначе будут неправильно обрабатываться гиперссылки в XHTML.</span><br />';
        }

        // Проверка загрузки необходимых расширений PHP
        echo '<br /><b><u>Расширения PHP</u></b><br />';
        if (extension_loaded('mysql'))
        {
            echo '<span class="green">OK</span> - mysql<br />';
        } else
        {
            $err = 1;
            echo '<span class="red">ОШИБКА! - PHP расширение "mysql" не загружено.</span><br />';
        }
        if (extension_loaded('gd'))
        {
            echo '<span class="green">OK</span> - gd<br />';
        } else
        {
            $err = 1;
            echo '<span class="red">ОШИБКА! - PHP расширение "gd" не загружено.</span><br />';
        }
        if (extension_loaded('zlib'))
        {
            echo '<span class="green">OK</span> - zlib<br />';
        } else
        {
            $err = 1;
            echo '<span class="red">ОШИБКА! - PHP расширение "zlib" не загружено.</span><br />';
        }
        if (extension_loaded('iconv'))
        {
            echo '<span class="green">OK</span> - iconv<br />';
        } else
        {
            $err = 1;
            echo '<span class="red">ОШИБКА! - PHP расширение "iconv" не загружено.</span><br />';
        }
        if (extension_loaded('mbstring'))
        {
            echo '<span class="green">OK</span> - mb_string<br />';
        } else
        {
            $err = 1;
            echo '<span class="red">ОШИБКА! - PHP расширение "mbstring" не загружено.</span><br />';
            echo 'Если Вы тестируете сайт локально на "Денвере", то там, в настройках по умолчанию данное расширение не подключено.<br />';
            echo 'Вам необходимо (для Денвера) открыть файл php.ini, который находится в папке /usr/local/php5 (или php4, в зависимости от версии) и отредактировать строку ;extension=php_mbstring.dll убрав точку с запятой в начале строки.';
        }

        // Проверка прав доступа к файлам и папкам
        function permissions($filez) {
            $filez = @decoct(@fileperms("../$filez")) % 1000;
            return $filez;
        }
        $cherr = "";

        // Проверка прав доступа к папкам
        $arr = array("incfiles/", "gallery/foto/", "gallery/temp/", "library/files/", "library/temp/", "pratt/", "forum/files/", "forum/temtemp/", "download/arctemp/", "download/files/", "download/graftemp/", "download/screen/", "download/mp3temp/",
            "download/upl/");
        foreach ($arr as $v)
        {
            if (permissions($v) < 777)
            {
                $cherr = $cherr . '<span class="red">ОШИБКА!</span> - ' . $v . '<br/><span class="gray">Необходимо установить права доступа 777.</span><br />';
                $err = 1;
            } else
            {
                $cherr = $cherr . '<span class="green">OK</span> - ' . $v . '<br/>';
            }
        }

        // Проверка прав доступа к файлам
        $arr = array("flood.dat", "library/java/textfile.txt", "library/java/META-INF/MANIFEST.MF");
        foreach ($arr as $v)
        {
            if (permissions($v) < 666)
            {
                $cherr = $cherr . '<span class="red">ОШИБКА!</span> - ' . $v . '<br/><span class="gray">Необходимо установить права доступа 666.</span><br />';
                $err = 1;
            } else
            {
                $cherr = $cherr . '<span class="green">OK</span> - ' . $v . '<br/>';
            }
        }

        echo '<br /><b><u>Права доступа</u></b><br />';
        echo $cherr;
        echo '<hr />';
        switch ($err)
        {
            case '1':
                echo '<span class="red">ВНИМАНИЕ!</span> Имеются критические ошибки!<br />Вы не сможете продолжить инсталляцию, пока не устраните их.<br />';
                echo '<a href="index.php">Проверить заново</a>';
                break;

            case '2':
                echo '<span class="red">ВНИМАНИЕ!</span> Имеются ошибки в конфигурации!<br />Вы можете продолжить инсталляцию, однако нормальная работа системы не гарантируется.<br />';
                echo '<a href="index.php">Проверить заново</a><br /><a href="index.php?act=db">Продолжить установку</a>';
                break;

            default:
                echo '<span class="green">ОТЛИЧНО!</span><br />Все настройки правильные<br /><br /><a href="index.php?act=db">Установка системы</a><br /><br />';
        }
}

echo "</body></html>";

function split_sql($sql)
{
    $sql = trim($sql);
    $sql = ereg_replace("\n#[^\n]*\n", "\n", $sql);
    $buffer = array();
    $ret = array();
    $in_string = false;
    for ($i = 0; $i < strlen($sql) - 1; $i++)
    {
        if ($sql[$i] == ";" && !$in_string)
        {
            $ret[] = substr($sql, 0, $i);
            $sql = substr($sql, $i + 1);
            $i = 0;
        }
        if ($in_string && ($sql[$i] == $in_string) && $buffer[1] != "\\")
        {
            $in_string = false;
        } elseif (!$in_string && ($sql[$i] == '"' || $sql[$i] == "'") && (!isset($buffer[0]) || $buffer[0] != "\\"))
        {
            $in_string = $sql[$i];
        }
        if (isset($buffer[1]))
        {
            $buffer[0] = $buffer[1];
        }
        $buffer[1] = $sql[$i];
    }
    if (!empty($sql))
    {
        $ret[] = $sql;
    }
    return ($ret);
}

?>