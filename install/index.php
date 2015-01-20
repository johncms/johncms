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
set_time_limit(1200);

define('INSTALL_VERSION', '5.3.0'); // Инсталлируемая версия
define('MODE', 'install');

class install
{
    /*
    -----------------------------------------------------------------
    Критические ошибки настройки PHP
    -----------------------------------------------------------------
    */
    static function check_php_errors()
    {
        $error = array();
        if (version_compare(phpversion(), '5.1.0', '<')) $error[] = 'PHP ' . phpversion();
        if (!extension_loaded('mysql')) $error[] = 'mysql';
        if (!extension_loaded('gd')) $error[] = 'gd';
        if (!extension_loaded('zlib')) $error[] = 'zlib';
        if (!extension_loaded('mbstring')) $error[] = 'mbstring';
        return !empty($error) ? $error : false;
    }

    /*
    -----------------------------------------------------------------
    Некритические предупреждения настройки PHP
    -----------------------------------------------------------------
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
            '/files/lng_edit/',
            '/files/users/album/',
            '/files/users/avatar/',
            '/files/users/photo/',
            '/files/mail/',
            '/gallery/foto/',
            '/gallery/temp/',
            '/incfiles/',
            '/library/temp/'
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
        $files = array(
            '/library/java/textfile.txt',
            '/library/java/META-INF/MANIFEST.MF'
        );
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
                    if (!mysql_query($ret[$i])) {
                        $errors[] = mysql_error();
                    }
                }
            }
        } else {
            $errors[] = 'ERROR: SQL file';
        }
        return $errors;
    }
}

/*
-----------------------------------------------------------------
Показываем инсталлятор
-----------------------------------------------------------------
*/
if (is_dir(MODE) && file_exists(MODE . '/install.php')) {
    if (file_exists('../incfiles/db.php')) {
        require('../incfiles/core.php');
    } else {
        $act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : false;
        session_name('SESID');
        session_start();
    }

    // Загружаем язык интерфейса
    if (isset($_POST['lng']) && ($_POST['lng'] == 'ru' || $_POST['lng'] == 'en')) $_SESSION['language'] = $_POST['lng'];
    $language = isset($_SESSION['language']) ? $_SESSION['language'] : 'en';
    $lng_file = 'languages/' . $language . '.lng';
    if (file_exists($lng_file)) {
        $lng = parse_ini_file($lng_file) or die('ERROR: language file');
    } else {
        die('ERROR: Language file is missing');
    }

    ob_start();
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n" .
        '<html xmlns="http://www.w3.org/1999/xhtml">' . "\n" .
        '<head>' . "\n" .
        '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . "\n" .
        '<title>JohnCMS ' . INSTALL_VERSION . (MODE == 'install' ? '' : ' | ' . $lng['update_from'] . ' ' . UPDATE_VERSION) . '</title>' . "\n" .
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
        '<h1 class="green">JohnCMS <span class="red">' . INSTALL_VERSION . '</span></h1>' . (MODE == 'install' ? '' : '<h3>' . $lng['update_from'] . ' ' . UPDATE_VERSION . '</h3>') . '<hr />';
    if (!$act) {
        echo '<form action="index.php" method="post">' .
            '<p><h3 class="green">' . $lng['change_language'] . '</h3>' .
            '<div><input type="radio" name="lng" value="en" ' . ($language == 'en' ? 'checked="checked"' : '') . ' />&#160;English</div>' .
            '<div><input type="radio" name="lng" value="ru" ' . ($language == 'ru' ? 'checked="checked"' : '') . ' />&#160;Русский</div>' .
            '</p><p><input type="submit" name="submit" value="' . $lng['change'] . '" /></p></form>' .
            '<p>' . $lng['languages'] . '</p>' .
            '<hr />';
    }
    require(MODE . '/install.php');
    echo '<hr />&copy;&#160;Powered by <a href="http://johncms.com">JohnCMS</a></body></html>';
} else {
    echo "<h2>FATAL ERROR: can't begin installation</h2>";
}