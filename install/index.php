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
// Визуальный мод инсталлятора от Piks                                        //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_JOHNCMS', 1);
header("Cache-Control: no-cache, must-revalidate");
header("Content-type: application/xhtml+xml; charset=UTF-8");
$version = 'JohnCMS 2.0.0';
$codename = '';
echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>
<head><meta http-equiv='content-type' content='application/xhtml+xml; charset=utf-8'/>";
echo '<link rel="shortcut icon" href="ico.gif" />
<title>' . $version . '' . $codename . ' - Установка системы</title>
<link rel="stylesheet" href="style.css" type="text/css" /></head><body>';

echo '<div class="head"><b>' . $version . '</b>' . $codename . '<br />';
switch ($_GET['act']) {
	case 'demo':
	echo 'Демо данные';
	break;
	case 'admin':
	echo 'Установки сайта';
	break;
	case 'db':
	echo 'Настройки соединения';
	break;
	case 'check':
	echo 'Проверка сервера';
	break;
	default:
	echo 'Установка системы';
	break;
}
echo '</div><div class="txt">';
switch ($_GET['act'])
{
    case 'set':
        ////////////////////////////////////////////////////////////
        // Создание таблиц в базе данных MySQL                    //
        ////////////////////////////////////////////////////////////
        require_once ("../incfiles/db.php");
        require_once ("../incfiles/func.php");
        $connect = mysql_connect($db_host, $db_user, $db_pass) or die('cannot connect to server</div></body></html>');
        mysql_select_db($db_name) or die('cannot connect to db</div></body></html>');
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
            echo '<span class="green">Oк</span> - Таблицы созданы<br />';

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
			`preg`='1';") or die('Ошибка настройки администратора</div></body></html>');
            $user_id = mysql_insert_id();
            echo '<span class="green">Oк</span> - администратор настроен<br />';

            // Импорт настроек
            mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($log) . "' WHERE `key`='nickadmina';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($meil) . "' WHERE `key`='emailadmina';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string(trim($_POST['wcopyright'])) . "' WHERE `key`='copyright';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($hom) . "' WHERE `key`='homeurl';");
            echo '<span class="green">Oк</span> - настройки импортированы<br />';

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
            echo '<span class="green">Oк</span> - викторина импортирована (' . $i . ' вопросов)<br /><br />';
            echo "Поздравляем! Установка " . $version . "" . $codename . " закончена.<br />Не забудьте:<br />1) Сменить права к папке incfiles на 755<br />2) Сменить права на файл incfiles/db.php 644<br />3) Удалить папку install с сайта.";
            // Установка ДЕМО данных
            echo '<div class="menu" style="margin-bottom: 5px;"><div class="tmenu">Демо Данные</div><div class="smenu">При желании, Вы можете установить <a href="index.php?act=demo&amp;id=' . $user_id . '&amp;ps=' . $_POST['wpassadmina'] . '">Демо данные</a><br />Это может быть полезно для начинающих сайтостроителей.<br />В базу будут внесены некоторые исходные настроики и материалы.</div></div>';
            echo "<p class='step'><a class='button' href='../auto.php?id=" . $user_id . "&amp;p=" . $_POST['wpassadmina'] . "'>Вход на сайт</a></p>";
        } else
        {
            // Если были ошибки, выводим их
            echo $error;
            echo '<br /><span class="red">Error!</span><br />При создании таблиц возникла ошибка.<br />Продолжение невозможно.';
        }
        break;

    case 'demo':
        ////////////////////////////////////////////////////////////
        // Установка ДЕМО данных                                  //
        ////////////////////////////////////////////////////////////
        require_once ("../incfiles/db.php");
        require_once ("../incfiles/func.php");
        $connect = mysql_connect($db_host, $db_user, $db_pass) or die('cannot connect to server</div></body></html>');
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
            echo '<br /><span class="red">Error!</span><br />В процессе установки ДЕМО данных возникли ошибки.<br />';
        }
        echo "Поздравляем! Установка " . $version . "" . $codename . " закончена.<br />Не забудьте:<br />1) Сменить права к папке incfiles на 755<br />2) Сменить права на файл incfiles/db.php 644<br />3) Удалить папку install с сайта.<br />";
        echo "<p style='step'><a class='button' href='../auto.php?id=" . $_GET['id'] . "&amp;p=" . $_GET['ps'] . "'>Вход на сайт</a></p>";
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
        echo 'Введите следующую информацию. Не волнуйтесь, вы всегда сможете изменить эти настройки.';
        echo '<form method="post" action="index.php?act=set">';
        echo '<div class="conf">Ваш ник:<br/><input name="wnickadmina" maxlength="50" value="Admin" /></div>';
        echo '<div class="conf">Ваш пароль:<br/><input name="wpassadmina" maxlength="50" value="password" /></div>';
        echo '<div class="conf">Ваш e-mail<br/><input name="wemailadmina" maxlength="50" /></div>';
        echo '<div class="conf">Копирайт:<br/><input name="wcopyright" maxlength="100" /></div>';
        echo '<div class="conf">Главная сайта без слэша в конце:<br/><input name="whome" maxlength="100" value="http://' . $_SERVER["SERVER_NAME"] . '" /></div><div style="padding-left: 2px; margin-left: 1px; margin-top: 8px;"><input value="Установить" type="submit" class="button" /></div></form>';
        break;

    case 'db':
        ////////////////////////////////////////////////////////////
        // Настройка соединения с MySQL                           //
        ////////////////////////////////////////////////////////////
        echo '<form action="index.php?act=admin" method="post">';
        echo 'Ниже вы должны ввести настройки соединения с базой данных MySQL.<br />Если вы не уверенны в них, свяжитесь с вашим хостинг-провайдером.';
        echo '<div class="conf">Сервер<br /><input type="text" name="host" value="localhost"/></div>';
        echo '<div class="conf">Название базы<br /><input type="text" name="name" value="johncms"/></div>';
        echo '<div class="conf">Имя пользователя<br /><input type="text" name="user" value="root"/></div>';
        echo '<div class="conf">MySQL пароль<br /><input type="text" name="pass"/></div>';
        echo '<div style="padding-left: 2px; margin-left: 1px; margin-top: 8px;"><input type="submit" class="button" value="Отправить"/></div></form>';
        break;

    case 'check':
        ////////////////////////////////////////////////////////////
        // Предварительная проверка системы                       //
        // 1) Проверка настроек PHP                               //
        // 2) Проверка необходимых расширений PHP                 //
        // 2) Проверка прав доступа к файлам и папкам             //
        ////////////////////////////////////////////////////////////
        $err = false;

        // Проверка настроек PHP
        echo '<div class="menu"><div class="tmenu">Настройки PHP</div>';
        if (version_compare(phpversion(), '4.1.0', '>'))
        {
            echo '<div class="smenu"><span class="green">Ок</span> - Версия PHP ' . phpversion() . '</div>';
        } else
        {
            $err = 1;
            echo '<div class="smenu"><span class="red">Ошибка!</span> - Версия PHP ' . phpversion() . '<br />';
            echo '<span class="gray">Эта версия PHP устаревшая и не поддерживается системой.</span></div>';
        }
        if (!ini_get('register_globals'))
        {
            echo '<div class="smenu"><span class="green">Ок</span> - register_globals OFF</div>';
        } else
        {
            $err = 2;
            echo '<div class="smenu"><span class="red">Внимание!</span> - register_globals OFF<br />';
            echo '<span class="gray">Вы можете продолжить установку, однако система в большей степени будет подвержена уязвимостям.</span></div>';
        }
        if (ini_get('arg_separator.output') == '&amp;')
        {
            echo '<div class="smenu"><span class="green">Ок</span> - arg_separator.output "&amp;amp;"</div>';
        } else
        {
            $err = 2;
            echo '<div class="smenu"><span class="red">Внимание!</span> - arg_separator.output "' . htmlentities(ini_get('arg_separator.output')) . '"<br />';
            echo '<span class="gray">Вы можете продолжить установку, однако настоятельно рекомендуется установить этот параметр на "&amp;amp;",<br /> иначе будут неправильно обрабатываться гиперссылки в xHTML.</span></div>';
        }
        echo '</div>';

        // Проверка загрузки необходимых расширений PHP
        echo '<div class="menu"><div class="tmenu">Расширения PHP</div>';
        if (extension_loaded('mysql'))
        {
            echo '<div class="smenu"><span class="green">Ок</span> - mysql</div>';
        } else
        {
            $err = 1;
            echo '<div class="smenu"><span class="red">Ошибка!</span> - PHP расширение "mysql" не загружено.</div>';
        }
        if (extension_loaded('gd'))
        {
            echo '<div class="smenu"><span class="green">Ок</span> - gd</div>';
        } else
        {
            $err = 1;
            echo '<div class="smenu"><span class="red">Ошибка!</span> - PHP расширение "gd" не загружено.</div>';
        }
        if (extension_loaded('zlib'))
        {
            echo '<div class="smenu"><span class="green">Ок</span> - zlib</div>';
        } else
        {
            $err = 1;
            echo '<div class="smenu"><span class="red">Ошибка!</span> - PHP расширение "zlib" не загружено.</div>';
        }
        if (extension_loaded('iconv'))
        {
            echo '<div class="smenu"><span class="green">Oк</span> - iconv</div>';
        } else
        {
            $err = 1;
            echo '<div class="smenu"><span class="red">Ошибка!</span> - PHP расширение "iconv" не загружено.</div>';
        }
        if (extension_loaded('mbstring'))
        {
            echo '<div class="smenu"><span class="green">Ок</span> - mb_string</div>';
        } else
        {
            $err = 1;
            echo '<div class="smenu"><span class="red">Ошибка!</span> - PHP расширение "mbstring" не загружено.<br />';
            echo '<span class="gray">Если Вы тестируете сайт локально на "Денвере", то там, в настройках по умолчанию данное расширение не подключено.<br />';
            echo 'Вам необходимо (для Денвера) открыть файл php.ini, который находится в папке /usr/local/php5 (или php4, в зависимости от версии) и отредактировать строку ;extension=php_mbstring.dll убрав точку с запятой в начале строки.</span></div>';
        }
        echo '</div>';

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
                $cherr = $cherr . '<div class="smenu"><span class="red">Ошибка!</span> - ' . $v . '<br /><span class="gray">Необходимо установить права доступа 777.</span></div>';
                $err = 1;
            } else
            {
                $cherr = $cherr . '<div class="smenu"><span class="green">Oк</span> - ' . $v . '</div>';
            }
        }

        // Проверка прав доступа к файлам
        $arr = array('flood.dat', 'library/java/textfile.txt', 'library/java/META-INF/MANIFEST.MF', 'panel/filebase.dat');
        foreach ($arr as $v)
        {
            if (permissions($v) < 666)
            {
                $cherr = $cherr . '<div class="smenu"><span class="red">Ошибка!</span> - ' . $v . '<br/><span class="gray">Необходимо установить права доступа 666.</span></div>';
                $err = 1;
            } else
            {
                $cherr = $cherr . '<div class="smenu"><span class="green">Ок</span> - ' . $v . '</div>';
            }
        }

        echo '<div class="menu"><div class="tmenu">Права доступа</div>';
        echo $cherr;
        echo '</div><br />';
        switch ($err)
        {
            case '1':
                echo '<span class="red">Внимание!</span> Имеются критические ошибки!<br />Вы не сможете продолжить инсталляцию, пока не устраните их.';
                echo '<p clss="step"><a class="button" href="index.php?act=check">Проверить заново</a></p>';
                break;

            case '2':
                echo '<span class="red">Внимание!</span> Имеются ошибки в конфигурации!<br />Вы можете продолжить инсталляцию, однако нормальная работа системы не гарантируется.';
                echo '<p class="step"><a class="button" href="index.php?act=check">Проверить заново</a> <a class="button" href="index.php?act=db">Продолжить установку</a></p>';
                break;

            default:
                echo '<span class="green">Отлично!</span><br />Все настройки правильные.<p class="step"><a class="button" href="index.php?act=db">Продолжить установку</a></p>';
        }
	break;
	
	default:
	///////////////////////////////////////////////////////
	//Приветствие										 //
	///////////////////////////////////////////////////////
	echo '<p>Добро пожаловать в JohnCMS.<br />Перед началом инсталляции, настоятельно рекомендуем ознакомиться с инструкцией, в файле <a href="../install.txt">install.txt</a>.';
	echo '<br />Список изменений, в сравнении с предыдущей версией, находится в файле <a href="../version.txt">version.txt</a>.</p>';
	echo '<p>Дополнительную информацию Вы можете получить на официальном сайте проекта <a href="http://johncms.com">johncms.com</a>,<br />или на доп. сайте поддержки <a href="http://gazenwagen.com">gazenwagen.com</a>.</p>';
	echo '<p>Установка и использование скриптов JohnCMS, означает полное согласие с условиями <a href="../license.txt">лицензии</a>.</p>';
	echo '<p class="step"><a class="button" href="index.php?act=check">Начать установку</a></p>';
	break;
}

echo '</div></body></html>';

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