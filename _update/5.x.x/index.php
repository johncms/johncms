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

define('VERSION', '6.0.0'); // Инсталлируемая версия
define('UPDATE_VERSION', '5.x.x'); // Обновление с версии

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
        if (version_compare(phpversion(), '5.2.0', '<')) $error[] = 'PHP ' . phpversion();
        if (!extension_loaded('mysql')) $error[] = 'mysql';
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

if (file_exists('../incfiles/db.php')) {
    require('../incfiles/core.php');
} else {
    die('ERROR: db.php does not exists.');
}

if (core::$user_rights != 9) {
    die('ERROR: to start the update process requires <a href="../login.php">administrator privileges</a>');
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

echo '<!DOCTYPE html>' . "\n" .
    '<html lang="' . $language . '">' . "\n" .
    '<head>' . "\n" .
    '<meta charset="utf-8">' . "\n" .
    '<title>JohnCMS ' . VERSION . ', update from ' . UPDATE_VERSION . '</title>' . "\n" .
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
    '<h1>JohnCMS <span class="red">' . VERSION . '</span></h1><h3>' . $lng['update_from'] . ' ' . UPDATE_VERSION . '</h3><hr />';
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
    case 'update_manual':
        echo '<a href="?">&lt;&lt; ' . $lng['back'] . '</a><br><br><br>';
        if (($changelog = file_get_contents('UPDATE.md')) !== false) {
            require_once('../incfiles/lib/Parsedown.php');
            $parsedown = new Parsedown();
            echo $parsedown->text($changelog);
        }
        break;

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
        functions::smileys(0, 2);
        echo '<h3 class="blue">' . $lng['congratulations'] . '</h3>' .
            $lng['successfully_updated'] . '<p><ul>' .
            '<li><a href="../panel">' . $lng['admin_panel'] . '</a></li>' .
            '<li><a href="../">' . $lng['to_site'] . '</a></li>' .
            '</ul></p>' .
            $lng['final_note'];
        break;

    case 'update':
        /*
        -----------------------------------------------------------------
        Процедура обновления
        -----------------------------------------------------------------
        */
        if (!isset($_SESSION['updated'])) {
            install::parse_sql('update.5.x.x.sql');

            // Переносим структуру каталогов
            $sql = mysql_query("SELECT `id`, `refid`, `text`, `ip` FROM `lib` WHERE `type`='cat'");

            while ($row = mysql_fetch_assoc($sql)) {
                mysql_query("
                  INSERT INTO `library_cats`
                  SET
                    `id`          = " . $row['id'] . ",
                    `parent`      = " . $row['refid'] . ",
                    `dir`         = " . $row['ip'] . ",
                    `pos`         = " . $row['id'] . ",
                    `name`        = '" . $row['text'] . "',
                    `description` = ''
                ");
            }

            // Переносим статьи
            $sql = mysql_query("SELECT `id`, `refid`, `text`, `announce`, `avtor`, `name`, `moder`, `count`, `time` FROM `lib` WHERE `type`='bk'");

            while ($row = mysql_fetch_assoc($sql)) {
                $req = mysql_query("SELECT `id` FROM `users` WHERE `name`='" . $row['avtor'] . "' LIMIT 1");

                if(mysql_num_rows($req)){
                    $res = mysql_fetch_assoc($req);
                    $uploader_id = $res['id'];
                } else {
                    $uploader_id = 0;
                }

                mysql_query("
                  INSERT INTO `library_texts`
                  SET
                    `id`          = " . $row['id'] . ",
                    `cat_id`      = " . $row['refid'] . ",
                    `name`        = '" . $row['name'] . "',
                    `announce`    = '" . mysql_real_escape_string($row['announce']) . "',
                    `text`        = '" . mysql_real_escape_string($row['text']) . "',
                    `uploader`    = '" . $row['avtor'] . "',
                    `uploader_id` = '" . $uploader_id . "',
                    `premod`      = " . $row['moder'] . ",
                    `count_views` = " . $row['count'] . ",
                    `time`        = '" . $row['time'] . "'
                ");
            }

            // Переносим комментарии
            $array = array();
            $sql = mysql_query("SELECT `id`,`refid`, `avtor`, `text`, `ip`, `soft`, `time` FROM `lib` WHERE `type`='komm'");

            while ($row = mysql_fetch_assoc($sql)) {
                $attributes = array(
                    'author_name'         => $row['avtor'],
                    'author_ip'           => $row['ip'],
                    'author_ip_via_proxy' => '',
                    'author_browser'      => $row['soft']
                );
                $array[$row['refid']][] = $row['id'];

                $req = mysql_query("SELECT `id` FROM `users` WHERE `name` = '" . $row['avtor'] . "' LIMIT 1");

                if(mysql_num_rows($req)){
                    $res = mysql_fetch_assoc($req);
                    mysql_query("
                      INSERT INTO `cms_library_comments`
                      SET
                        `sub_id`     = " . $row['refid'] . ",
                        `time`       = '" . $row['time'] . "',
                        `user_id`    = " . $res['id'] . ",
                        `text`       = '" . $row['text'] . "',
                        `attributes` = '" . mysql_real_escape_string(serialize($attributes)) . "'
                    ");

                    foreach ($array as $aid => $cnt) {
                        mysql_query("UPDATE `library_texts` SET `count_comments`=" . count($cnt) . ", `comments`=1 WHERE `id`=" . $aid);
                    }
                }
            }

            mysql_query('DROP TABLE `lib`');

            $_SESSION['updated'] = 1;
        }

        echo '<p><a href="?act=final">' . $lng['continue'] . '</a></p>';
        break;

    default:
        /*
        -----------------------------------------------------------------
        Проверка настроек PHP и прав доступа
        -----------------------------------------------------------------
        */
        $search = array('#UPDATE_VERSION#');
        $replace = array(UPDATE_VERSION);
        echo str_replace($search, $replace, $lng['update_warning']);
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
                '<a href="index.php?act=update">' . $lng['ignore_warnings'] . '</a>';
        } else {
            echo '<form action="index.php?act=update" method="post"><p><input type="submit" value="' . $lng['update'] . '"/></p></form>';
        }
}

echo '<hr />&copy;&#160;Powered by <a href="http://johncms.com">JohnCMS</a></body></html>';
