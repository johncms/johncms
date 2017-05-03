<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

const JOHNCMS = '7.1.0';

// Check the current PHP version
if (version_compare(PHP_VERSION, '5.6', '<')) {
    die('<div style="text-align: center; font-size: xx-large"><strong>ERROR!</strong><br>Your needs PHP 5.6 or higher</div>');
}

require '../system/vendor/autoload.php';

class install
{
    /**
     * Критические ошибки настройки PHP
     *
     * @return array|bool
     */
    public static function checkPhpErrors()
    {
        $error = [];
        if (version_compare(phpversion(), '5.5.0', '<')) {
            $error[] = 'PHP ' . phpversion();
        }

        if (!class_exists(PDO::class)) {
            $error[] = 'PDO';
        }

        if (!extension_loaded('gd')) {
            $error[] = 'gd';
        }

        if (!extension_loaded('zlib')) {
            $error[] = 'zlib';
        }

        if (!extension_loaded('mbstring')) {
            $error[] = 'mbstring';
        }

        return !empty($error) ? $error : false;
    }

    /**
     * Некритические предупреждения настройки PHP
     *
     * @return array|bool
     */
    public static function check_php_warnings()
    {
        $error = [];
        if (ini_get('register_globals')) {
            $error[] = 'register_globals';
        }

        return !empty($error) ? $error : false;
    }

    /**
     * Проверяем права доступа к папкам
     *
     * @return array|bool
     */
    public static function check_folders_rights()
    {
        $folders = [
            '/files/cache/',
            '/files/downloads/files/',
            '/files/downloads/screen/',
            '/files/forum/attach/',
            '/files/forum/topics/',
            '/files/library/',
            '/files/library/tmp',
            '/files/library/images',
            '/files/library/images/big',
            '/files/library/images/orig',
            '/files/library/images/small',
            '/files/users/album/',
            '/files/users/avatar/',
            '/files/users/photo/',
            '/files/mail/',
            '/system/config/',
        ];
        $error = [];

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
    public static function check_files_rights()
    {
        $error = [];

        if (is_file('../system/config/database.local.php') && !is_writable('../system/config/database.local.php')) {
            $error[] = '/system/config/database.local.php';
        }

        return !empty($error) ? $error : false;
    }

    /*
    -----------------------------------------------------------------
    Парсинг SQL файла
    -----------------------------------------------------------------
    */
    public static function parse_sql($file = false, PDO $pdo)
    {
        $errors = [];
        if ($file && file_exists($file)) {
            $query = fread(fopen($file, 'r'), filesize($file));
            $query = trim($query);
            $query = preg_replace("/\n\#[^\n]*/", '', "\n" . $query);
            $buffer = [];
            $ret = [];
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
        foreach ($error as $val) {
            $out .= '<div>' . $val . '</div>';
        }
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

session_name('SESID');
session_start();

// Загружаем язык интерфейса
if (isset($_POST['lng']) && ($_POST['lng'] == 'ru' || $_POST['lng'] == 'en')) {
    $_SESSION['language'] = $_POST['lng'];
}

$language = isset($_SESSION['language']) ? $_SESSION['language'] : 'en';
$lng_file = __DIR__ . '/locale/' . $language . '/install.php';

if (file_exists($lng_file)) {
    $lng = include $lng_file;
} else {
    die('ERROR: Language file is missing');
}

ob_start();
echo '<!DOCTYPE html>' . "\n" .
    '<html lang="' . $language . '">' . "\n" .
    '<head>' . "\n" .
    '<meta charset="utf-8">' . "\n" .
    '<title>JohnCMS ' . JOHNCMS . '</title>' . "\n" .
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
    '<h1>JohnCMS ' . JOHNCMS . '</h1><hr />';
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
    case 'doc':
        break;

    case 'changelog':
        echo '<a href="?">&lt;&lt; ' . $lng['back'] . '</a><br><br><br>';
        if (($changelog = file_get_contents('../CHANGELOG.md')) !== false) {
            $parsedown = new Parsedown();
            echo $parsedown->text($changelog);
        }
        break;

    case 'license':
        echo '<a href="?">&lt;&lt; ' . $lng['back'] . '</a><br><br><br>';
        if (($changelog = file_get_contents('../LICENSE.md')) !== false) {
            $parsedown = new Parsedown();
            echo $parsedown->text($changelog);
        }
        break;

    case 'final':
        // Установка завершена
        //TODO: разобраться с обновлением смайлов
        echo '<span class="st">' . $lng['check_1'] . '</span><br />' .
            '<span class="st">' . $lng['database'] . '</span><br />' .
            '<span class="st">' . $lng['site_settings'] . '</span>' .
            '<h2 class="green">' . $lng['final'] . '</h2>' .
            '<hr />';
        echo '<h3 class="blue">' . $lng['congratulations'] . '</h3>' .
            $lng['installation_completed'] . '<p><ul>' .
            '<li><a href="../admin">' . $lng['admin_panel'] . '</a></li>' .
            '<li><a href="../index.php">' . $lng['to_site'] . '</a></li>' .
            '</ul></p>' .
            $lng['final_warning'];
        break;

    case 'set':
        // Создание базы данных и Администратора системы
        $db_check = false;
        $db_error = [];
        $site_error = [];
        $admin_error = [];

        // Принимаем данные формы
        $db_host = isset($_POST['dbhost']) ? htmlentities(trim($_POST['dbhost'])) : 'localhost';
        $db_name = isset($_POST['dbname']) ? htmlentities(trim($_POST['dbname'])) : 'johncms';
        $db_user = isset($_POST['dbuser']) ? htmlentities(trim($_POST['dbuser'])) : 'root';
        $db_pass = isset($_POST['dbpass']) ? htmlentities(trim($_POST['dbpass'])) : '';
        $site_url = isset($_POST['siteurl']) ? preg_replace("#/$#", '', htmlentities(trim($_POST['siteurl']), ENT_QUOTES, 'UTF-8')) : 'http://' . $_SERVER["SERVER_NAME"];
        $site_mail = isset($_POST['sitemail']) ? htmlentities(trim($_POST['sitemail']), ENT_QUOTES, 'UTF-8') : '@';
        $admin_user = isset($_POST['admin']) ? trim($_POST['admin']) : 'admin';
        $admin_pass = isset($_POST['password']) ? trim($_POST['password']) : '';
        $demo = isset($_POST['demo']);

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
                    $pdo = new \PDO('mysql:host=' . $db_host . ';dbname=' . $db_name, $db_user, $db_pass,
                        [
                            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
                            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
                        ]
                    );
                } catch (\PDOException $e) {
                    $pdo_error = $e->getMessage();

                    if (stristr($pdo_error, 'no such host')) {
                        $db_error['host'] = $lng['error_db_host'];
                    } elseif (stristr($pdo_error, 'access denied for user')) {
                        $db_error['access'] = $lng['error_db_user'];
                    } elseif (stristr($pdo_error, 'unknown database')) {
                        $db_error['name'] = $lng['error_db_name'];
                    } else {
                        $db_error['unknown'] = $lng['error_db_unknown'] . ' ' . $pdo_error;
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
            if (mb_strlen($admin_user) < 2 || mb_strlen($admin_user) > 15) {
                $admin_error['admin'] = $lng['error_admin_lenght'];
            }

            // Проверяем ник Админа на допустимые символы
            if (preg_match("/[^\dA-Za-z\-\@\*\(\)\?\!\~\_\=\[\]]+/", $admin_user)) {
                $admin_error['admin'] = $lng['error_nick_symbols'];
            }

            // Проверяем пароль Админа
            if (empty($admin_pass)) {
                $admin_error['pass'] = $lng['error_password_empty'];
            }

            // Проверяем длину пароля Админа
            if (mb_strlen($admin_pass) < 5) {
                $admin_error['pass'] = $lng['error_password_lenght'];
            }

            // Если предварительные проверки прошли, заливаем базу данных
            if ($db_check && empty($site_error) && empty($admin_error)) {
                // Создаем системный файл database.local.php
                $pdoattr = [
                    'pdo' => [
                        'db_host' => $db_host,
                        'db_name' => $db_name,
                        'db_user' => $db_user,
                        'db_pass' => $db_pass,
                    ],
                ];
                $dbfile = "<?php\n\n" . 'return ' . var_export($pdoattr, true) . ";\n";

                if (!file_put_contents('../system/config/database.local.php', $dbfile)) {
                    echo 'ERROR: Can not write database.local.php</body></html>';
                    exit;
                }

                // Заливаем базу данных
                $sql = install::parse_sql(__DIR__ . '/sql/install.sql', $pdo);

                if (!empty($sql)) {
                    foreach ($sql as $val) {
                        echo $val . '<br />';
                    }
                    echo '</body></html>';
                    exit;
                }

                // Читаем каталог с файлами языков
                $lng_list = [];

                foreach (glob('../system/locale/*/lng.ini') as $val) {
                    $tmp = explode('/', dirname($val));
                    $iso = array_pop($tmp);
                    $desc = parse_ini_file($val);
                    $lng_list[$iso] = isset($desc['name']) && !empty($desc['name']) ? $desc['name'] : $iso;
                }

                $systemSettings = [
                    'johncms' => [
                        'active'        => 1,
                        'antiflood'     => [
                            'mode'    => 2,
                            'day'     => 10,
                            'night'   => 30,
                            'dayfrom' => 10,
                            'dayto'   => 22,
                        ],
                        'clean_time'    => 0,
                        'copyright'     => 'Powered by JohnCMS',
                        'email'         => $site_mail,
                        'flsz'          => '16000',
                        'gzip'          => 1,
                        'homeurl'       => $site_url,
                        'karma'         => [
                            'karma_points' => 5,
                            'karma_time'   => 86400,
                            'forum'        => 20,
                            'time'         => 0,
                            'on'           => 1,
                            'adm'          => 0,
                        ],
                        'lng'           => $language,
                        'lng_list'      => $lng_list,
                        'mod_reg'       => 2,
                        'mod_forum'     => 2,
                        'mod_guest'     => 2,
                        'mod_lib'       => 2,
                        'mod_lib_comm'  => 1,
                        'mod_down'      => 2,
                        'mod_down_comm' => 1,
                        'meta_key'      => 'johncms',
                        'meta_desc'     => 'Powered by JohnCMS http://johncms.com',
                        'news'          => [
                            'view'     => 1,
                            'size'     => 200,
                            'quantity' => 3,
                            'days'     => 7,
                            'breaks'   => true,
                            'smileys'  => false,
                            'tags'     => true,
                            'kom'      => true,
                        ],
                        'skindef'       => 'default',
                        'timeshift' => 0,
                    ],
                ];
                $configFile = "<?php\n\n" . 'return ' . var_export($systemSettings, true) . ";\n";

                if (!file_put_contents('../system/config/system.local.php', $configFile)) {
                    echo 'ERROR: Can not write system.local.php</body></html>';
                    exit;
                }

                // Создаем Администратора
                $stmt = $pdo->prepare("INSERT INTO `users` SET
                      `name`     = ?,
                      `name_lat` = ?,
                      `password` = ?,
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
                    md5(md5($admin_pass)),
                    $site_mail,
                    $site_url,
                    htmlentities($_SERVER["HTTP_USER_AGENT"]),
                ]);

                $user_id = $pdo->lastInsertId();

                // Устанавливаем сессию и COOKIE c данными администратора
                $_SESSION['uid'] = $user_id;
                $_SESSION['ups'] = md5(md5($admin_pass));
                setcookie("cuid", base64_encode($user_id), time() + 3600 * 24 * 365);
                setcookie("cups", md5($admin_pass), time() + 3600 * 24 * 365);

                // Установка ДЕМО данных
                if ($demo) {
                    $demo_data = install::parse_sql(__DIR__ . '/sql/demo.sql', $pdo);
                }

                // Установка завершена
                header('Location: index.php?act=final');
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
            '<input type="password" name="dbpass" value="' . $db_pass . '"' . ($db_check ? ' readonly="readonly" style="background-color: #CCFFCC"' : '') . (isset($db_error['access']) ? ' style="background-color: #FFCCCC"' : '') . '>';

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
                '<input type="text" name="password" value="' . $admin_pass . '"' . (isset($admin_error['pass']) ? ' style="background-color: #FFCCCC"' : '') . '><br />' .
                '<small class="gray">' . $lng['admin_password_help'] . '</small></p>' .
                '<p><input type="checkbox" name="demo" value="1"><small class="blue">&#160;<b>' . $lng['install_demo'] . '</b></small><br />' .
                '<small class="gray">' . $lng['install_demo_help'] . '</small></p>' .
                '<p><input type="submit" name="install" value="' . $lng['setup'] . '"></p>';
        } else {
            echo '<p><input type="submit" name="check" value="' . $lng['check'] . '"></p>';
        }

        echo '</form>';
        echo '<p><a href="index.php?act=set">' . $lng['reset_form'] . '</a></p>';
        break;

    default:
        if (is_file('../system/config/database.local.php') || is_file('../system/config/system.local.php')) {
            echo '<h1 class="red">' . $lng['error'] . '</h1>';
            echo '<h2 class="red">' . $lng['already_installed'] . '</h2>';
            echo '<p>' . $lng['to_install_again'] . '.</p>';
            echo '<ul><li>/system/config/<strong class="red">database.local.php</strong></li><li>/system/config/<strong class="red">system.local.php</strong></li></ul>';
        } else {
            // Проверка настроек PHP и прав доступа
            echo '<p>' . $lng['install_note'] . '</p>';
            echo '<p><h3 class="green">' . $lng['check_1'] . '</h3>';

            // Проверка критических ошибок PHP
            if (($php_errors = install::checkPhpErrors()) !== false) {
                echo '<h3>' . $lng['php_critical_error'] . '</h3><ul>';
                foreach ($php_errors as $val) {
                    echo '<li>' . $val . '</li>';
                }
                echo '</ul>';
            }

            // Проверка предупреждений PHP
            if (($php_warnings = install::check_php_warnings()) !== false) {
                echo '<h3>' . $lng['php_warnings'] . '</h3><ul>';
                foreach ($php_warnings as $val) {
                    echo '<li>' . $val . '</li>';
                }
                echo '</ul>';
            }

            // Проверка прав доступа к папкам
            if (($folders = install::check_folders_rights()) !== false) {
                echo '<h3>' . $lng['access_rights'] . ' 777</h3><ul>';
                foreach ($folders as $val) {
                    echo '<li>' . $val . '</li>';
                }
                echo '</ul>';
            }

            // Проверка прав доступа к файлам
            if (($files = install::check_files_rights()) !== false) {
                echo '<h3>' . $lng['access_rights'] . ' 666</h3><ul>';
                foreach ($files as $val) {
                    echo '<li>' . $val . '</li>';
                }
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
}

echo '<hr />&copy;&#160;Powered by <a href="http://johncms.com">JohnCMS</a></body></html>';
