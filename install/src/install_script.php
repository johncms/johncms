<?php

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
$site_url = isset($_POST['siteurl']) ? preg_replace('#/$#', '', htmlentities(trim($_POST['siteurl']), ENT_QUOTES, 'UTF-8')) : 'http://' . $_SERVER['SERVER_NAME'];
$site_mail = isset($_POST['sitemail']) ? htmlentities(trim($_POST['sitemail']), ENT_QUOTES, 'UTF-8') : '@';
$admin_user = isset($_POST['admin']) ? trim($_POST['admin']) : 'admin';
$admin_pass = isset($_POST['password']) ? trim($_POST['password']) : '';
$demo = isset($_POST['demo']);

// Проверяем заполнение реквизитов базы данных
if (isset($_POST['check']) || isset($_POST['install'])) {
    if (empty($db_host)) {
        $db_error['host'] = Installer::$lang['error_db_host_empty'];
    }

    if (empty($db_name)) {
        $db_error['name'] = Installer::$lang['error_db_name_empty'];
    }

    if (empty($db_user)) {
        $db_error['user'] = Installer::$lang['error_db_user_empty'];
    }

    // Проверяем подключение к серверу базы данных
    if (empty($db_error)) {
        try {
            $pdo = new \PDO(
                'mysql:host=' . $db_host . ';dbname=' . $db_name, $db_user, $db_pass,
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
                $db_error['host'] = Installer::$lang['error_db_host'];
            } elseif (stristr($pdo_error, 'access denied for user')) {
                $db_error['access'] = Installer::$lang['error_db_user'];
            } elseif (stristr($pdo_error, 'unknown database')) {
                $db_error['name'] = Installer::$lang['error_db_name'];
            } else {
                $db_error['unknown'] = Installer::$lang['error_db_unknown'] . ' ' . $pdo_error;
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
        $site_error['url'] = Installer::$lang['error_siteurl_empty'];
    }

    // Проверяем наличие ника Админа
    if (empty($admin_user)) {
        $admin_error['admin'] = Installer::$lang['error_admin_empty'];
    }

    // Проверяем ник Админа на длину
    if (mb_strlen($admin_user) < 2 || mb_strlen($admin_user) > 15) {
        $admin_error['admin'] = Installer::$lang['error_admin_lenght'];
    }

    // Проверяем ник Админа на допустимые символы
    if (preg_match("/[^\dA-Za-z\-\@\*\(\)\?\!\~\_\=\[\]]+/", $admin_user)) {
        $admin_error['admin'] = Installer::$lang['error_nick_symbols'];
    }

    // Проверяем пароль Админа
    if (empty($admin_pass)) {
        $admin_error['pass'] = Installer::$lang['error_password_empty'];
    }

    // Проверяем длину пароля Админа
    if (mb_strlen($admin_pass) < 5) {
        $admin_error['pass'] = Installer::$lang['error_password_lenght'];
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

        if (! file_put_contents('../config/autoload/database.local.php', $dbfile)) {
            echo 'ERROR: Can not write database.local.php</body></html>';
            exit;
        }

        // Заливаем базу данных
        $sql = Installer::parseSql(__DIR__ . '/../sql/install.sql', $pdo);

        if (! empty($sql)) {
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
            $lng_list[$iso] = isset($desc['name']) && ! empty($desc['name']) ? $desc['name'] : $iso;
        }

        $systemSettings = [
            'johncms' => [
                'active'                  => 1,
                'antiflood'               => [
                    'mode'    => 2,
                    'day'     => 10,
                    'night'   => 30,
                    'dayfrom' => 10,
                    'dayto'   => 22,
                ],
                'clean_time'              => 0,
                'copyright'               => 'Powered by JohnCMS',
                'email'                   => $site_mail,
                'flsz'                    => '16000',
                'gzip'                    => 1,
                'user_email_required'     => 0,
                'user_email_confirmation' => 0,
                'homeurl'                 => $site_url,
                'karma'                   => [
                    'karma_points' => 5,
                    'karma_time'   => 86400,
                    'forum'        => 20,
                    'time'         => 0,
                    'on'           => 1,
                    'adm'          => 0,
                ],
                'lng'                     => Installer::getLang(),
                'lng_list'                => $lng_list,
                'mod_reg'                 => 2,
                'mod_forum'               => 2,
                'mod_guest'               => 2,
                'mod_lib'                 => 2,
                'mod_lib_comm'            => 1,
                'mod_down'                => 2,
                'mod_down_comm'           => 1,
                'meta_title'              => 'JohnCMS',
                'meta_key'                => 'johncms',
                'meta_desc'               => 'Powered by JohnCMS http://johncms.com',
                'news'                    => [
                    'view'     => 1,
                    'size'     => 200,
                    'quantity' => 3,
                    'days'     => 7,
                    'breaks'   => true,
                    'smileys'  => false,
                    'tags'     => true,
                    'kom'      => true,
                ],
                'skindef'                 => 'default',
                'timeshift'               => 0,
            ],
        ];
        $configFile = "<?php\n\n" . 'return ' . var_export($systemSettings, true) . ";\n";

        if (! file_put_contents('../config/autoload/system.local.php', $configFile)) {
            echo 'ERROR: Can not write system.local.php</body></html>';
            exit;
        }

        // Создаем Администратора
        $stmt = $pdo->prepare(
            "INSERT INTO `users` SET
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
                      `ip` = '" . ip2long($_SERVER['REMOTE_ADDR']) . "',
                      `browser` = ?,
                      `preg` = '1',
                      `email_confirmed` = '1'
                      "
        );
        $stmt->execute(
            [
                $admin_user,
                mb_strtolower($admin_user),
                md5(md5($admin_pass)),
                $site_mail,
                $site_url,
                htmlentities($_SERVER['HTTP_USER_AGENT']),
            ]
        );

        $user_id = $pdo->lastInsertId();

        // Устанавливаем сессию и COOKIE c данными администратора
        $_SESSION['uid'] = $user_id;
        $_SESSION['ups'] = md5(md5($admin_pass));
        setcookie('cuid', $user_id, time() + 3600 * 24 * 365);
        setcookie('cups', md5($admin_pass), time() + 3600 * 24 * 365);

        // Установка ДЕМО данных
        if ($demo) {
            $demo_data = Installer::parseSql(__DIR__ . '/../sql/demo.sql', $pdo);
        }

        // Установка завершена
        header('Location: index.php?act=final');
        exit();
    }
}
