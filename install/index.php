<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

define('_IN_JOHNCMS', 1);

define('VERSION', '6.2.2'); // Инсталлируемая версия

class install
{
    /**
     * Критические ошибки настройки PHP
     *
     * @return array|bool
     */
    static function check_php_errors()
    {
        $error = array();
        if (version_compare(phpversion(), '5.5.0', '<')) $error[] = 'PHP ' . phpversion();
        if (!class_exists('PDO')) $error[] = 'PDO';
        if (!extension_loaded('gd')) $error[] = 'gd';
        if (!extension_loaded('zlib')) $error[] = 'zlib';
        if (!extension_loaded('mbstring')) $error[] = 'mbstring';

        return !empty($error) ? $error : false;
    }

    /**
     * Некритические предупреждения настройки PHP
     *
     * @return array|bool
     */
    static function check_php_warnings()
    {
        $error = array();
        if (ini_get('register_globals')) $error[] = 'register_globals';

        return !empty($error) ? $error : false;
    }

    /**
     * Проверяем права доступа к папкам
     *
     * @return array|bool
     */
    static function check_folders_rights()
    {
        $folders = array(
            '/download/arctemp/',
            '/download/files/',
            '/download/graftemp/',
            '/download/screen/',
            '/files/cache/',
            '/files/forum/attach/',
            '/files/library/',
            '/files/library/tmp',
            '/files/library/images',
            '/files/library/images/big',
            '/files/library/images/orig',
            '/files/library/images/small',
            '/files/lng_edit/',
            '/files/users/album/',
            '/files/users/avatar/',
            '/files/users/photo/',
            '/files/mail/',
            '/gallery/foto/',
            '/gallery/temp/',
            '/incfiles/'
        );
        $error = array();

        foreach ($folders as $val) {
            if (!is_writable('..' . $val)) {
                $error[] = $val;
            }
        }

        return !empty($error) ? $error : false;
    }

    /**
     * Проверяем права доступа к файлам
     *
     * @return array|bool
     */
    static function check_files_rights()
    {
        $files = array();
        $error = array();

        foreach ($files as $val) {
            if (!is_writable('..' . $val)) {
                $error[] = $val;
            }
        }

        return !empty($error) ? $error : false;
    }

    /*
    -----------------------------------------------------------------
    Парсинг SQL файла
    -----------------------------------------------------------------
    */
    static function parse_sql($file = false)
    {
        global $pdo;

        $errors = array();
        if ($file && file_exists($file)) {
            $query = fread(fopen($file, 'r'), filesize($file));
            $query = trim($query);
            $query = preg_replace("/\n\#[^\n]*/", '', "\n" . $query);
            $buffer = array();
            $ret = array();
            $in_string = false;
            for ($i = 0; $i < strlen($query) - 1; $i++) {
                if ($query[$i] == ";" && !$in_string) {
                    $ret[] = substr($query, 0, $i);
                    $query = substr($query, $i + 1);
                    $i = 0;
                }
                if ($in_string && ($query[$i] == $in_string) && $buffer[1] != "\\") {
                    $in_string = false;
                } elseif (!$in_string && ($query[$i] == '"' || $query[$i] == "'") && (!isset($buffer[0]) || $buffer[0] != "\\")) {
                    $in_string = $query[$i];
                }
                if (isset($buffer[1])) {
                    $buffer[0] = $buffer[1];
                }
                $buffer[1] = $query[$i];
            }
            if (!empty($query)) {
                $ret[] = $query;
            }
            for ($i = 0; $i < count($ret); $i++) {
                $ret[$i] = trim($ret[$i]);
                if (!empty($ret[$i]) && $ret[$i] != "#") {
                    try {
                        $pdo->query($ret[$i]);
                    } catch (PDOException $e) {
                        $errors[] = $e->getMessage();
                    }
                }
            }
        } else {
            $errors[] = 'ERROR: SQL file';
        }

        return $errors;
    }
}

function show_errors($error)
{
    global $lng;
    if (!empty($error)) {
        // Показываем ошибки
        $out = '<div class="red" style="margin-bottom: 4px"><b>' . $lng['error'] . '</b>';
        foreach ($error as $val) $out .= '<div>' . $val . '</div>';
        $out .= '</div>';

        return $out;
    } else {
        return false;
    }
}

/*
-----------------------------------------------------------------
Показываем инсталлятор
-----------------------------------------------------------------
*/
$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : false;

if (file_exists('../incfiles/db.php') && $act == 'final') {
    require('../incfiles/core.php');
} else {
    session_name('SESID');
    session_start();
}


// Загружаем язык интерфейса
if (isset($_POST['lng']) && ($_POST['lng'] == 'ru' || $_POST['lng'] == 'en')) {
    $_SESSION['language'] = $_POST['lng'];
}

$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'en';
$lng_file = $language . '.lng';

if (file_exists($lng_file)) {
    $lng = parse_ini_file($lng_file) or die('ERROR: language file');
} else {
    die('ERROR: Language file is missing');
}

ob_start();
echo '<!DOCTYPE html>' . "\n" .
    '<html lang="' . $language . '">' . "\n" .
    '<head>' . "\n" .
    '<meta charset="utf-8">' . "\n" .
    '<title>JohnCMS ' . VERSION . '</title>' . "\n" .
    '<style type="text/css">' .
    'a, a:link, a:visited{color: blue;}' .
    'body {font-family: Arial, Helvetica, sans-serif; font-size: small; color: #000000; background-color: #FFFFFF}' .
    'h1{margin: 0; padding: 0; padding-bottom: 4px;}' .
    'h2{margin: 0; padding: 0; padding-bottom: 4px;}' .
    'h3{margin: 0; padding: 0; padding-bottom: 2px;}' .
    'ul{margin:0; padding-left:20px; }' .
    'li{padding-bottom: 6px; }' .
    '.red{color: #FF0000;}' .
    '.green{color: #009933;}' .
    '.blue{color: #0000EE;}' .
    '.gray{color: gray;}' .
    '.pgl{padding-left: 8px}' .
    '.select{color: blue; font-size: medium; font-weight: bold}' .
    '.small{font-size: x-small}' .
    '.st{color: gray; text-decoration: line-through}' .
    '</style>' . "\n" .
    '</head>' . "\n" .
    '<body>' . "\n" .
    '<h1>JohnCMS ' . VERSION . '</h1><hr />';
if (!$act) {
    echo '<form action="index.php" method="post">' .
        '<p><h3 class="green">' . $lng['change_language'] . '</h3>' .
        '<div><input type="radio" name="lng" value="en" ' . ($language == 'en' ? 'checked="checked"' : '') . ' />&#160;English</div>' .
        '<div><input type="radio" name="lng" value="ru" ' . ($language == 'ru' ? 'checked="checked"' : '') . ' />&#160;Русский</div>' .
        '</p><p><input type="submit" name="submit" value="' . $lng['change'] . '" /></p></form>' .
        '<p>' . $lng['languages'] . '</p>' .
        '<hr />';
}

switch ($act) {
    case 'changelog':
        echo '<a href="?">&lt;&lt; ' . $lng['back'] . '</a><br><br><br>';
        if (($changelog = file_get_contents('../CHANGELOG.md')) !== false) {
            require_once('../incfiles/lib/Parsedown.php');
            $parsedown = new Parsedown();
            echo $parsedown->text($changelog);
        }
        break;

    case 'license':
        echo '<a href="?">&lt;&lt; ' . $lng['back'] . '</a><br><br><br>';
        if (($changelog = file_get_contents('../LICENSE.md')) !== false) {
            require_once('../incfiles/lib/Parsedown.php');
            $parsedown = new Parsedown();
            echo $parsedown->text($changelog);
        }
        break;

    case 'final':
        /*
        -----------------------------------------------------------------
        Установка завершена
        -----------------------------------------------------------------
        */
        echo '<span class="st">' . $lng['check_1'] . '</span><br />' .
            '<span class="st">' . $lng['database'] . '</span><br />' .
            '<span class="st">' . $lng['site_settings'] . '</span>' .
            '<h2 class="green">' . $lng['final'] . '</h2>' .
            '<hr />';
        echo '<h3 class="blue">' . $lng['congratulations'] . '</h3>' .
            $lng['installation_completed'] . '<p><ul>' .
            '<li><a href="../panel">' . $lng['admin_panel'] . '</a></li>' .
            '<li><a href="../index.php">' . $lng['to_site'] . '</a></li>' .
            '</ul></p>' .
            $lng['final_warning'];
        break;

    case 'set':
        /*
        -----------------------------------------------------------------
        Создание базы данных и Администратора системы
        -----------------------------------------------------------------
        */
        $db_check = false;
        $db_error = array();
        $site_error = array();
        $admin_error = array();

        // Принимаем данные формы
        $db_host = isset($_POST['dbhost']) ? htmlentities(trim($_POST['dbhost'])) : 'localhost';
        $db_name = isset($_POST['dbname']) ? htmlentities(trim($_POST['dbname'])) : 'johncms';
        $db_user = isset($_POST['dbuser']) ? htmlentities(trim($_POST['dbuser'])) : 'root';
        $db_pass = isset($_POST['dbpass']) ? htmlentities(trim($_POST['dbpass'])) : '';
        $site_url = isset($_POST['siteurl']) ? preg_replace("#/$#", '', htmlentities(trim($_POST['siteurl']), ENT_QUOTES, 'UTF-8')) : 'http://' . $_SERVER["SERVER_NAME"];
        $site_mail = isset($_POST['sitemail']) ? htmlentities(trim($_POST['sitemail']), ENT_QUOTES, 'UTF-8') : '@';
        $admin_user = isset($_POST['admin']) ? trim($_POST['admin']) : 'admin';
        $admin_pass = isset($_POST['password']) ? trim($_POST['password']) : '';

        // Проверяем заполнение реквизитов базы данных
        if (isset($_POST['check']) || isset($_POST['install'])) {
            if (empty($db_host)) {
                $db_error['host'] = $lng['error_db_host_empty'];
            }

            if (empty($db_name)) {
                $db_error['name'] = $lng['error_db_name_empty'];
            }

            if (empty($db_user)) {
                $db_error['user'] = $lng['error_db_user_empty'];
            }

            // Проверяем подключение к серверу базы данных
            if (empty($db_error)) {
                try {
                    $pdo = new PDO('mysql:host=' . $db_host . ';dbname=' . $db_name . ';charset=utf8mb4', $db_user, $db_pass,
                        array (
                            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
                        )
                    );
                } catch (PDOException $e) {
                    switch ($e->getCode()) {
                        case 1045:
                            $db_error['access'] = $lng['error_db_user'];
                            break;
                        case 1049:
                            $db_error['name'] = $lng['error_db_name'];
                            break;
                        case 2002:
                            $db_error['host'] = $lng['error_db_host'];
                            break;
                        default:
                            $db_error['unknown'] = $lng['error_db_unknown'];
                    }
                }
            }

            if (empty($db_error)) {
                $db_check = true;
            }
        }

        if ($db_check && isset($_POST['install'])) {
            // Проверяем URL сайта
            if (empty($site_url)) {
                $site_error['url'] = $lng['error_siteurl_empty'];
            }

            // Проверяем наличие ника Админа
            if (empty($admin_user)) {
                $admin_error['admin'] = $lng['error_admin_empty'];
            }

            // Проверяем ник Админа на длину
            if (mb_strlen($admin_user) < 5 || mb_strlen($admin_user) > 20) {
                $admin_error['admin'] = $lng['error_admin_lenght'];
            }

            // Проверяем ник Админа на допустимые символы
            if (preg_match('/[^[:alnum:]_.]/', $admin_user)) {
                $admin_error['admin'] = $lng['error_nick_symbols'];
            }

            // Проверяем пароль Админа
            if (empty($admin_pass)) {
                $admin_error['pass'] = $lng['error_password_empty'];
            }

            // Проверяем длину пароля Админа
            if (mb_strlen($admin_pass) < 6) {
                $admin_error['pass'] = $lng['error_password_lenght'];
            }

            // Если предварительные проверки прошли, заливаем базу данных
            if ($db_check && empty($site_error) && empty($admin_error)) {
                // Создаем системный файл db.php
                $dbfile = "<?php\r\n" .
                    "defined('_IN_JOHNCMS') or die ('Error: restricted access');\r\n\r\n" .
                    '$db_host = ' . "'$db_host';\r\n" .
                    '$db_name = ' . "'$db_name';\r\n" .
                    '$db_user = ' . "'$db_user';\r\n" .
                    '$db_pass = ' . "'$db_pass';";
                if (!file_put_contents('../incfiles/db.php', $dbfile)) {
                    echo 'ERROR: Can not write db.php</body></html>';
                    exit;
                }

                // Заливаем базу данных
                $sql = install::parse_sql('install.sql');
                if (!empty($sql)) {
                    foreach ($sql as $val) echo $val . '<br />';
                    echo '</body></html>';
                    exit;
                }

                // Записываем системные настройки
                $stmt = $pdo->prepare('UPDATE `cms_settings` SET `val` = ? WHERE `key` = ? LIMIT 1');
                $stmt->execute([$language, 'lng']);
                $stmt->execute([$site_url, 'homeurl']);
                $stmt->execute([$site_mail, 'email']);

                // Создаем Администратора
                $stmt = $pdo->prepare("INSERT INTO `users` SET
                    `name` = ?,
                    `name_lat` = ?,
                    `password` = '" . md5(md5($admin_pass)) . "',
                    `sex` = 'm',
                    `datereg` = '" . time() . "',
                    `lastdate` = '" . time() . "',
                    `mail` = ?,
                    `www` = ?,
                    `about` = '',
                    `set_user` = '',
                    `set_forum` = '',
                    `set_mail` = '',
                    `smileys` = '',
                    `rights` = '9',
                    `ip` = '" . ip2long($_SERVER["REMOTE_ADDR"]) . "',
                    `browser` = ?,
                    `preg` = '1'
                ");
                $stmt->execute([
                    $admin_user,
                    mb_strtolower($admin_user),
                    $site_mail,
                    $site_url,
                    $_SERVER['HTTP_USER_AGENT']
                ]);
                $user_id = $pdo->lastInsertId();

                // Устанавливаем сессию и COOKIE c данными администратора
                $_SESSION['uid'] = $user_id;
                $_SESSION['ups'] = md5(md5($admin_pass));
                setcookie("cuid", base64_encode($user_id), time() + 3600 * 24 * 365);
                setcookie("cups", md5($admin_pass), time() + 3600 * 24 * 365);

                // Установка завершена
                header('Location: index.php?act=final'); exit;
            }
        }

        echo '<span class="st">' . $lng['check_1'] . '</span>';

        if ($db_check) {
            echo '<br /><span class="st">' . $lng['database'] . '</span>' .
                '<h2 class="green">' . $lng['site_settings'] . '</h2>';
        } else {
            echo '<h2 class="green">' . $lng['database'] . '</h2>' .
                '<span class="gray">' . $lng['site_settings'] . '</span><br />';
        }

        echo '<span class="gray">' . $lng['final'] . '</span>' .
            '<hr />' .
            '<form action="index.php?act=set" method="post">' .
            show_errors($db_error) .
            '<small class="blue"><b>MySQL Host:</b></small><br />' .
            '<input type="text" name="dbhost" value="' . $db_host . '"' . ($db_check ? ' readonly="readonly" style="background-color: #CCFFCC"' : '') . (isset($db_error['host']) ? ' style="background-color: #FFCCCC"' : '') . '><br />' .
            '<small class="blue"><b>MySQL Database:</b></small><br />' .
            '<input type="text" name="dbname" value="' . $db_name . '"' . ($db_check ? ' readonly="readonly" style="background-color: #CCFFCC"' : '') . (isset($db_error['name']) ? ' style="background-color: #FFCCCC"' : '') . '><br />' .
            '<small class="blue"><b>MySQL User:</b></small><br />' .
            '<input type="text" name="dbuser" value="' . $db_user . '"' . ($db_check ? ' readonly="readonly" style="background-color: #CCFFCC"' : '') . (isset($db_error['access']) || isset($db_error['user']) ? ' style="background-color: #FFCCCC"' : '') . '><br />' .
            '<small class="blue"><b>MySQL Password:</b></small><br />' .
            '<input type="text" name="dbpass" value="' . $db_pass . '"' . ($db_check ? ' readonly="readonly" style="background-color: #CCFFCC"' : '') . (isset($db_error['access']) ? ' style="background-color: #FFCCCC"' : '') . '>';

        if ($db_check) {
            // Настройки Сайта
            echo '<p>' . show_errors($site_error) .
                '<small class="blue"><b>' . $lng['site_url'] . ':</b></small><br />' .
                '<input type="text" name="siteurl" value="' . $site_url . '"' . (isset($site_error['url']) ? ' style="background-color: #FFCCCC"' : '') . '><br />' .
                '<small class="gray">' . $lng['site_url_help'] . '</small><br />' .
                '<small class="blue"><b>' . $lng['site_email'] . ':</b></small><br />' .
                '<input type="text" name="sitemail" value="' . $site_mail . '"><br />' .
                '<small class="gray">' . $lng['site_email_help'] . '</small></p>' .
                '<p>' . show_errors($admin_error) .
                '<small class="blue"><b>' . $lng['admin_login'] . ':</b></small><br />' .
                '<input type="text" name="admin" value="' . $admin_user . '"' . (isset($admin_error['admin']) ? ' style="background-color: #FFCCCC"' : '') . '><br />' .
                '<small class="gray">' . $lng['admin_login_help'] . '</small><br />' .
                '<small class="blue"><b>' . $lng['admin_password'] . ':</b></small><br />' .
                '<input type="text" name="password" value="' . htmlspecialchars($admin_pass, ENT_QUOTES, 'UTF-8') . '"' . (isset($admin_error['pass']) ? ' style="background-color: #FFCCCC"' : '') . '><br />' .
                '<small class="gray">' . $lng['admin_password_help'] . '</small></p>' .
                '<p><input type="submit" name="install" value="' . $lng['setup'] . '"></p>';
        } else {
            echo '<p><input type="submit" name="check" value="' . $lng['check'] . '"></p>';
        }

        echo '</form>';
        echo '<p><a href="index.php?act=set">' . $lng['reset_form'] . '</a></p>';
        break;

    default:
        /*
        -----------------------------------------------------------------
        Проверка настроек PHP и прав доступа
        -----------------------------------------------------------------
        */
        echo '<p>' . $lng['install_note'] . '</p>';
        echo '<p><h3 class="green">' . $lng['check_1'] . '</h3>';

        // Проверка критических ошибок PHP
        if (($php_errors = install::check_php_errors()) !== false) {
            echo '<h3>' . $lng['php_critical_error'] . '</h3><ul>';
            foreach ($php_errors as $val) echo '<li>' . $val . '</li>';
            echo '</ul>';
        }

        // Проверка предупреждений PHP
        if (($php_warnings = install::check_php_warnings()) !== false) {
            echo '<h3>' . $lng['php_warnings'] . '</h3><ul>';
            foreach ($php_warnings as $val) echo '<li>' . $val . '</li>';
            echo '</ul>';
        }

        // Проверка прав доступа к папкам
        if (($folders = install::check_folders_rights()) !== false) {
            echo '<h3>' . $lng['access_rights'] . ' 777</h3><ul>';
            foreach ($folders as $val) echo '<li>' . $val . '</li>';
            echo '</ul>';
        }

        // Проверка прав доступа к файлам
        if (($files = install::check_files_rights()) !== false) {
            echo '<h3>' . $lng['access_rights'] . ' 666</h3><ul>';
            foreach ($files as $val) echo '<li>' . $val . '</li>';
            echo '</ul>';
        }

        if (!$php_errors && !$php_warnings && !$folders && !$files) {
            echo '<div class="pgl">' . $lng['configuration_successful'] . '</div>';
        }

        echo '</p>';

        if ($php_errors || $folders || $files) {
            echo '<h3 class="red">' . $lng['critical_errors'] . '</h3>' .
                '<h3><a href="index.php">' . $lng['check_again'] . '</a></h3>';
        } elseif ($php_warnings) {
            echo '<h3 class="red">' . $lng['are_warnings'] . '</h3>' .
                '<h3><a href="index.php">' . $lng['check_again'] . '</a></h3>' .
                '<a href="index.php?act=set">' . $lng['ignore_warnings'] . '</a>';
        } else {
            echo '<form action="index.php?act=set" method="post"><p><input type="submit" value="' . $lng['install'] . '"/></p></form>';
        }
}

echo '<hr />&copy;&#160;Powered by <a href="http://johncms.com">JohnCMS</a></body></html>';
