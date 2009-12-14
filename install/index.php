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

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml">' . "\n";
echo
"<title>JohnCMS 3.0 - Установка</title>
<style type='text/css'>
body {font-family: Arial, Helvetica, sans-serif; font-size: small; color: #000000; background-color: #FFFFFF}
h2{ margin: 0; padding: 0; padding-bottom: 4px; }
ul{ margin:0; padding-left:20px; }
li { padding-bottom: 6px; }
.red { color: #FF0000; font-weight: bold; }
.green{ color: #009933; font-weight: bold; }
.gray{ color: #999999; font: small; }
</style>
</head><body>";
echo '<h2 class="green">JohnCMS v.3.0.0</h2>Установка системы<hr />';

switch ($_GET['act']) {
    case 'set' :
        ////////////////////////////////////////////////////////////
        // Создание таблиц в базе данных MySQL                    //
        ////////////////////////////////////////////////////////////
        echo '<h2>Установка системы</h2><ul>';
        require_once ("../incfiles/db.php");
        require_once ("../incfiles/func.php");
        $connect = mysql_connect($db_host, $db_user, $db_pass) or die('cannot connect to server</div></body></html>');
        mysql_select_db($db_name) or die('cannot connect to db</div></body></html>');
        mysql_query("SET NAMES 'utf8'", $connect);
        $error = '';
        @ set_magic_quotes_runtime(0);
        // Читаем SQL файл и заносим его в базу данных
        $query = fread(fopen('data/install.sql', 'r'), filesize('data/install.sql'));
        $pieces = split_sql($query);
        for ($i = 0; $i < count($pieces); $i++) {
            $pieces[$i] = trim($pieces[$i]);
            if (!empty ($pieces[$i]) && $pieces[$i] != "#") {
                if (!mysql_query($pieces[$i])) {
                    $error = $error . mysql_error() . '<br />';
                }
            }
        }
        if (empty ($error)) {
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
			`password`='" . mysql_real_escape_string($par1) .
            "',
			`sex`='m',
			`datereg`='" . time() . "',
			`lastdate`='" . time() . "',
			`mail`='" . mysql_real_escape_string($meil) . "',
			`www`='" . mysql_real_escape_string($hom) . "',
			`rights`='9',
			`ip`='" . $ip .
            "',
			`browser`='" . mysql_real_escape_string($brow) . "',
			`preg`='1';") or die('Ошибка настройки администратора</div></body></html>');
            $user_id = mysql_insert_id();
            echo '<span class="green">Oк</span> - администратор настроен<br />';

            // Импорт настроек
            mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($meil) . "' WHERE `key`='emailadmina';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string(trim($_POST['wcopyright'])) . "' WHERE `key`='copyright';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($hom) . "' WHERE `key`='homeurl';");
            echo '<span class="green">Oк</span> - настройки импортированы<br />';

            // Импорт вопросов Викторины
            $file = file("data/vopros.txt");
            $count = count($file);
            for ($i = 0; $i < $count; $i++) {
                $tx = explode("||", $file[$i]);
                mysql_query("INSERT INTO `vik` SET
				`vopros`='" . mysql_real_escape_string(trim($tx[0])) . "',
				`otvet`='" . mysql_real_escape_string(trim($tx[1])) . "'
				");
            }
            echo '<span class="green">Oк</span> - викторина импортирована (' . $i . ' вопросов)</ul>';
            echo '<br /><h2 class="green">Установка завершена</h2>';
            // Установка ДЕМО данных
            echo '<ul>При желании, Вы можете установить <a href="index.php?act=demo&amp;id=' . $user_id . '&amp;ps=' . $_POST['wpassadmina'] .
            '">Демо данные</a><br />Это может быть полезно для начинающих сайтостроителей.<br />В базу будут внесены некоторые исходные настроики и материалы.</ul>';
            echo
            '<br /><h2 class="red">Не забудьте:</h2><ul><li>Сменить права к папке incfiles на 755</li><li>Сменить права на файл incfiles/db.php 644</li><li>Удалить папку install с сайта</li></ul>';
            echo '<hr /><a href="../auto.php?id=' . $user_id . '&amp;p=' . $_POST['wpassadmina'] . '">Вход на сайт</a>';
        }
        else {
            // Если были ошибки, выводим их
            echo $error;
            echo '<br /><span class="red">Error!</span><br />При создании таблиц возникла ошибка.<br />Продолжение невозможно.';
        }
        break;

    case 'demo' :
        ////////////////////////////////////////////////////////////
        // Установка ДЕМО данных                                  //
        ////////////////////////////////////////////////////////////
        require_once ("../incfiles/db.php");
        require_once ("../incfiles/func.php");
        $connect = mysql_connect($db_host, $db_user, $db_pass) or die('cannot connect to server</div></body></html>');
        mysql_select_db($db_name) or die('cannot connect to db');
        mysql_query("SET NAMES 'utf8'", $connect);
        $error = '';
        @ set_magic_quotes_runtime(0);
        // Читаем SQL файл и заносим его в базу данных
        $query = fread(fopen('data/demo.sql', 'r'), filesize('data/demo.sql'));
        $pieces = split_sql($query);
        for ($i = 0; $i < count($pieces); $i++) {
            $pieces[$i] = trim($pieces[$i]);
            if (!empty ($pieces[$i]) && $pieces[$i] != "#") {
                if (!mysql_query($pieces[$i])) {
                    $error = $error . mysql_error() . '<br />';
                }
            }
        }
        if (empty ($error)) {
            echo '<span class="green">OK</span> - ДЕМО данные установлены<br />';
        }
        else {
            // Если были ошибки, выводим их
            echo $error;
            echo '<br /><span class="red">Error!</span><br />В процессе установки ДЕМО данных возникли ошибки.<br />';
        }
        echo "Поздравляем! Установка " . $version . "" . $codename .
        " закончена.<br />Не забудьте:<br />1) Сменить права к папке incfiles на 755<br />2) Сменить права на файл incfiles/db.php 644<br />3) Удалить папку install с сайта.<br />";
        echo "<p style='step'><a class='button' href='../auto.php?id=" . $_GET['id'] . "&amp;p=" . $_GET['ps'] . "'>Вход на сайт</a></p>";
        break;

    case "admin" :
        ////////////////////////////////////////////////////////////
        // Настройки сайта и Администратора                       //
        ////////////////////////////////////////////////////////////
        $dhost = trim($_POST['host']);
        $duser = trim($_POST['user']);
        $dpass = trim($_POST['pass']);
        $dname = trim($_POST['name']);
        $text = "<?php\r\n\r\n" . "defined('_IN_JOHNCMS') or die ('Error: restricted access');\r\n\r\n" . "$" . "db_host=\"$dhost\";\r\n" . "$" . "db_user=\"$duser\";\r\n" . "$" . "db_pass=\"$dpass\";\r\n" . "$" . "db_name=\"$dname\";\r\n"
        . "\r\n?>";
        $fp = @ fopen("../incfiles/db.php", "w");
        fputs($fp, $text);
        fclose($fp);
        echo '<p>Создаем Администратора системы</p>';
        echo '<form method="post" action="index.php?act=set">';
        echo '<p><b>Ваш ник</b><br/><input name="wnickadmina" maxlength="50" value="Admin" /></p>';
        echo '<p><b>Ваш пароль</b><br/><input name="wpassadmina" maxlength="50" value="password" /></p>';
        echo '<p><b>Ваш e-mail</b><br/><input name="wemailadmina" maxlength="50" /></p>';
        echo '<p><b>Копирайт</b><br/><input name="wcopyright" maxlength="100" /></p>';
        echo '<p><b>Главная сайта</b> без слэша в конце<br/><input name="whome" maxlength="100" value="http://' . $_SERVER["SERVER_NAME"] . '" /></p>';
        echo '<hr /><input value="Продолжить" type="submit" class="button" /></form>';
        break;

    case 'db' :
        ////////////////////////////////////////////////////////////
        // Настройка соединения с MySQL                           //
        ////////////////////////////////////////////////////////////
        echo '<form action="index.php?act=admin" method="post">';
        echo
        '<p>Ниже вы должны ввести настройки соединения с базой данных MySQL.<br />Если вы не уверенны в них, свяжитесь с вашим хостинг-провайдером.</p>';
        echo '<p><b>Адрес сервера</b><br /><input type="text" name="host" value="localhost"/></p>';
        echo '<p><b>Название базы</b><br /><input type="text" name="name" value="johncms"/></p>';
        echo '<p><b>Имя пользователя</b><br /><input type="text" name="user" value="root"/></p>';
        echo '<p><b>MySQL пароль</b><br /><input type="text" name="pass"/></p>';
        echo '<hr /><input type="submit" class="button" value="Продолжить"/></form>';
        break;

    case 'check' :
        ////////////////////////////////////////////////////////////
        // Предварительная проверка системы                       //
        // 1) Проверка настроек PHP                               //
        // 2) Проверка необходимых расширений PHP                 //
        // 2) Проверка прав доступа к файлам и папкам             //
        ////////////////////////////////////////////////////////////
        $err = false;
        // Проверка настроек PHP
        echo '<h2>Настройки PHP</h2><ul>';
        if (version_compare(phpversion(), '5.1.0', '>')) {
            echo '<div><span class="green">OK</span> - Версия PHP ' . phpversion() . '</div>';
        }
        else {
            $err = 1;
            echo '<div><span class="red">ОШИБКА! - Версия PHP ' . phpversion() . ' устаревшая и не поддерживается системой.</span></div>';
        }
        if (!ini_get('register_globals')) {
            echo '<div><span class="green">OK</span> - register_globals OFF</div>';
        }
        else {
            $err = 2;
            echo
            '<div><span class="red">Внимание! - register_globals OFF</span><br /><span class="gray">Вы можете продолжить установку, однако система в большей степени будет подвержена уязвимостям.</span></div>';
        }
        if (ini_get('arg_separator.output') == '&amp;') {
            echo '<div><span class="green">OK</span> - arg_separator.output "&amp;amp;"</div>';
        }
        else {
            $err = 2;
            echo '<div><span class="red">Внимание! - arg_separator.output "' . htmlentities(ini_get('arg_separator.output')) . '"</span><br />';
            echo
            '<span class="gray">Вы можете продолжить установку, однако настоятельно рекомендуется установить этот параметр на "&amp;amp;",<br /> иначе будут неправильно обрабатываться гиперссылки в xHTML.</span></div>';
        }

        // Проверка загрузки необходимых расширений PHP
        echo '</ul><br /><h2>Расширения PHP</h2><ul>';
        if (extension_loaded('mysql')) {
            echo '<div><span class="green">OK</span> - mysql</div>';
        }
        else {
            $err = 1;
            echo '<div><span class="red">ОШИБКА! - расширение "mysql" не загружено</span></div>';
        }
        if (extension_loaded('gd')) {
            echo '<div><span class="green">OK</span> - gd</div>';
        }
        else {
            $err = 1;
            echo '<div><span class="red">ОШИБКА! - расширение "gd" не загружено</span></div>';
        }
        if (extension_loaded('zlib')) {
            echo '<div><span class="green">OK</span> - zlib</div>';
        }
        else {
            $err = 1;
            echo '<div><span class="red">ОШИБКА! - расширение "zlib" не загружено</span></div>';
        }
        if (extension_loaded('iconv')) {
            echo '<div><span class="green">OK</span> - iconv</div>';
        }
        else {
            $err = 1;
            echo '<div><span class="red">ОШИБКА! - расширение "iconv" не загружено</span></div>';
        }
        if (extension_loaded('mbstring')) {
            echo '<div><span class="green">OK</span> - mb_string</div>';
        }
        else {
            $err = 1;
            echo '<div><span class="red">Ошибка! - расширение "mbstring" не загружено</span><br />';
            echo
            '<span class="gray">Если Вы тестируете сайт локально на "Денвере", то там, в настройках по умолчанию данное расширение не подключено.<br />';
            echo
            'Вам необходимо (для Денвера) открыть файл php.ini, который находится в папке /usr/local/php5 (или php4, в зависимости от версии) и отредактировать строку ;extension=php_mbstring.dll убрав точку с запятой в начале строки.</span></div>';
        }

        // Проверка прав доступа к файлам и папкам
        function permissions($filez) {
            $filez = @ decoct(@ fileperms("../$filez")) % 1000;
            return $filez;
        }
        $cherr = '';

        // Проверка прав доступа к папкам
        $arr = array('files/avatar/', 'files/photo/', 'cache/', 'incfiles/', 'gallery/foto/', 'gallery/temp/', 'library/files/', 'library/temp/', 'pratt/', 'forum/files/', 'forum/temtemp/', 'download/arctemp/', 'download/files/', 'download/graftemp/', 'download/screen/',
        'download/mp3temp/', 'download/upl/');
        foreach ($arr as $v) {
            if (permissions($v) < 777) {
                $cherr = $cherr . '<div class="smenu"><span class="red">ОШИБКА! - ' . $v . '</span><br /><span class="gray">Необходимо установить права доступа 777.</span></div>';
                $err = 1;
            }
            else {
                $cherr = $cherr . '<div class="smenu"><span class="green">OK</span> - ' . $v . '</div>';
            }
        }

        // Проверка прав доступа к файлам
        $arr = array('library/java/textfile.txt', 'library/java/META-INF/MANIFEST.MF');
        foreach ($arr as $v) {
            if (permissions($v) < 666) {
                $cherr = $cherr . '<div class="smenu"><span class="red">ОШИБКА! - ' . $v . '</span><br/><span class="gray">Необходимо установить права доступа 666.</span></div>';
                $err = 1;
            }
            else {
                $cherr = $cherr . '<div class="smenu"><span class="green">OK</span> - ' . $v . '</div>';
            }
        }

        echo '</ul><br /><h2>Права доступа</h2><ul>';
        echo $cherr;
        echo '</ul><hr />';
        switch ($err) {
            case '1' :
                echo '<span class="red">Внимание!</span> Имеются критические ошибки!<br />Вы не сможете продолжить инсталляцию, пока не устраните их.';
                echo '<p clss="step"><a class="button" href="index.php?act=check">Проверить заново</a></p>';
                break;

            case '2' :
                echo
                '<span class="red">Внимание!</span> Имеются ошибки в конфигурации!<br />Вы можете продолжить инсталляцию, однако нормальная работа системы не гарантируется.';
                echo '<p class="step"><a class="button" href="index.php?act=check">Проверить заново</a> <a class="button" href="index.php?act=db">Продолжить установку</a></p>';
                break;

            default :
                echo '<span class="green">Отлично!</span><br />Все настройки правильные.<p><a class="button" href="index.php?act=db">Продолжить установку</a></p>';
        }
        break;

    default :
        ///////////////////////////////////////////////////////
        //Приветствие										 //
        ///////////////////////////////////////////////////////
        echo
        '<h2>Добро пожаловать в JohnCMS</h2><ul>
		<li>Перед началом инсталляции, настоятельно рекомендуем ознакомиться с инструкцией, в файле <a href="../install.txt">install.txt</a><br />
        Список изменений, в сравнении с предыдущей версией, находится в файле <a href="../version.txt">version.txt</a></li>
        <li>Дополнительную информацию Вы можете получить на официальном сайте проекта <a href="http://johncms.com">johncms.com</a>,<br />или на доп. сайте поддержки <a href="http://gazenwagen.com">gazenwagen.com</a>.</li>
        <li>Установка и использование скриптов JohnCMS, означает полное согласие с условиями <a href="../license.txt">лицензии</a></li>';
        echo '</ul><hr /><a class="button" href="index.php?act=check">Начать установку</a>';
        break;
}

echo '</body></html>';

function split_sql($sql) {
    $sql = trim($sql);
    $sql = ereg_replace("\n#[^\n]*\n", "\n", $sql);
    $buffer = array();
    $ret = array();
    $in_string = false;
    for ($i = 0; $i < strlen($sql) - 1; $i++) {
        if ($sql[$i] == ";" && !$in_string) {
            $ret[] = substr($sql, 0, $i);
            $sql = substr($sql, $i + 1);
            $i = 0;
        }
        if ($in_string && ($sql[$i] == $in_string) && $buffer[1] != "\\") {
            $in_string = false;
        }
        elseif (!$in_string && ($sql[$i] == '"' || $sql[$i] == "'") && (!isset ($buffer[0]) || $buffer[0] != "\\")) {
            $in_string = $sql[$i];
        }
        if (isset ($buffer[1])) {
            $buffer[0] = $buffer[1];
        }
        $buffer[1] = $sql[$i];
    }
    if (!empty ($sql)) {
        $ret[] = $sql;
    }
    return ($ret);
}

?>