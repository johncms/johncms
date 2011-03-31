<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

defined('INSTALL') or die('Error: restricted access');
function show_errors($error) {
    global $lng;

    if (!empty($error)) {
        // Показываем ошибки
        $out = '<div class="red" style="margin-bottom: 4px"><b>' . $lng['error'] . '</b>';
        foreach ($error as $val)$out .= '<div>' . $val . '</div>';
        $out .= '</div>';
        return $out;
    } else {
        return false;
    }
}
switch ($_GET['mod']) {
    case 'final':
        /*
        -----------------------------------------------------------------
        Установка завершена
        -----------------------------------------------------------------
        */
        functions::smileys(0, 2);
        echo '<h2 class="blue">' . $lng['congratulations'] . '</h2>' .
            $lng['installation_completed'] . '<p><ul>' .
            '<li><a href="index.php?act=languages&amp;lng_id=' . $lng_id . '">' . $lng['install_more_languages'] . '</a></li>' .
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
        $db_error = array ();
        $site_error = array ();
        $admin_error = array ();
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
        if (isset($_POST['check']) || isset($_POST['install'])) {
            // Проверяем заполнение реквизитов базы данных
            if (empty($db_host))
                $db_error['host'] = $lng['error_db_host_empty'];
            if (empty($db_name))
                $db_error['name'] = $lng['error_db_name_empty'];
            if (empty($db_user))
                $db_error['user'] = $lng['error_db_user_empty'];
            // Проверяем подключение к серверу базы данных
            if (empty($db_error)) {
                $con_err = false;
                @mysql_connect($db_host, $db_user, $db_pass) or $con_err = mysql_error();
                if ($con_err && stristr($con_err, 'no such host'))
                    $db_error['host'] = $lng['error_db_host'];
                elseif ($con_err && stristr($con_err, 'access denied for user'))
                    $db_error['access'] = $lng['error_db_user'];
                elseif ($con_err)
                    $db_error['unknown'] = $lng['error_db_unknown'];
            }
            // Проверяем наличие базы данных
            if (empty($db_error) && @mysql_select_db($db_name) == false)
                $db_error['name'] = $lng['error_db_name'];
            if (empty($db_error))
                $db_check = true;
            @mysql_close();
        }
        if ($db_check && isset($_POST['install'])) {
            // Проверяем URL сайта
            if (empty($site_url))
                $site_error['url'] = $lng['error_siteurl_empty'];
            // Проверяем ник Админа
            if (empty($admin_user))
                $admin_error['admin'] = $lng['error_admin_empty'];
            if (mb_strlen($admin_user) < 2 || mb_strlen($admin_user) > 15)
                $admin_error['admin'] = $lng['error_admin_lenght'];
            if (preg_match("/[^\dA-Za-z\-\@\*\(\)\?\!\~\_\=\[\]]+/", $admin_user))
                $admin_error['admin'] = $lng['error_nick_symbols'];
            // Проверяем пароль Админа
            if (empty($admin_pass))
                $admin_error['pass'] = $lng['error_password_empty'];
            if (mb_strlen($admin_pass) < 5 || mb_strlen($admin_pass) > 10)
                $admin_error['pass'] = $lng['error_password_lenght'];
            if (preg_match("/[^\dA-Za-z]+/", $admin_pass))
                $admin_error['pass'] = $lng['error_pass_symbols'];
            if ($db_check && empty($site_error) && empty($admin_error)) {
                // Создаем системный файл db.php
                $dbfile = "<?php\r\n\r\n" .
                    "defined('_IN_JOHNCMS') or die ('Error: restricted access');\r\n\r\n" .
                    '$db_host = ' . "'$db_host';\r\n" .
                    '$db_name = ' . "'$db_name';\r\n" .
                    '$db_user = ' . "'$db_user';\r\n" .
                    '$db_pass = ' . "'$db_pass';\r\n\r\n" .
                    '?>';
                if (!file_put_contents('../incfiles/db.php', $dbfile)) {
                    echo 'ERROR: Can not write db.php</body></html>';
                    exit;
                }
                // Соединяемся с базой данных
                $connect = mysql_connect($db_host, $db_user, $db_pass) or die('ERROR: cannot connect to DB server</body></html>');
                mysql_select_db($db_name) or die('ERROR: cannot select DB</body></html>');
                mysql_query("SET NAMES 'utf8'", $connect);
                // Заливаем базу данных
                require('includes/parse_sql.php');
                $sql = new parse_sql('data/install.sql');
                if (!empty($sql->errors)) {
                    foreach ($sql->errors as $val)echo $val . '<br />';
                    echo '</body></html>';
                    exit;
                }
                // Добавляем в базу системный язык
                $attr = serialize(array (
                    'author' => $lng_set[$lng_id]['author'],
                    'author_email' => $lng_set[$lng_id]['author_email'],
                    'author_url' => $lng_set[$lng_id]['author_url'],
                    'description' => $lng_set[$lng_id]['description'],
                    'version' => $lng_set[$lng_id]['version']
                ));
                mysql_query("INSERT INTO `cms_lng_list` SET
                    `iso` = '" . $lng_set[$lng_id]['iso'] . "',
                    `name` = '" . $lng_set[$lng_id]['name'] . "',
                    `build` = '" . $lng_set[$lng_id]['build'] . "',
                    `attr` = '" . mysql_real_escape_string($attr) . "'
                ");
                $lng_insert_id = mysql_insert_id();
                $lng_array = parse_ini_file('languages/' . $lng_set[$lng_id]['filename'] . '.ini', true);
                unset($lng_array['description']); // Удаляем описание языка
                unset($lng_array['install']);     // Удаляем фразы инсталлятора
                foreach ($lng_array as $module => $phr_array) {
                    foreach ($phr_array as $keyword => $phrase) {
                        mysql_query("INSERT INTO `cms_lng_phrases` SET
                            `language_id` = '$lng_insert_id',
                            `module` = '" . mysql_real_escape_string($module) . "',
                            `keyword` = '" . mysql_real_escape_string($keyword) . "',
                            `default` = '" . mysql_real_escape_string($phrase) . "'
                        ");
                    }
                }
                // Записываем системные настройки
                mysql_query("UPDATE `cms_settings` SET `val`='$lng_insert_id' WHERE `key`='lng_id'");
                mysql_query("UPDATE `cms_settings` SET `val`='" . $lng_set[$lng_id]['iso'] . "' WHERE `key`='lng_iso'");
                mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($site_url) . "' WHERE `key`='homeurl'");
                mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string($site_mail) . "' WHERE `key`='email'");
                // Создаем Администратора
                mysql_query("INSERT INTO `users` SET
                    `name` = '" . mysql_real_escape_string($admin_user) . "',
                    `name_lat` = '" . mysql_real_escape_string(mb_strtolower($admin_user)) . "',
                    `password` = '" . md5(md5($admin_pass)) . "',
                    `sex` = 'm',
                    `datereg` = '" . time() . "',
                    `lastdate` = '" . time() . "',
                    `mail` = '" . mysql_real_escape_string($site_mail) . "',
                    `www` = '" . mysql_real_escape_string($site_url) . "',
                    `rights` = '9',
                    `ip` = '" . ip2long($_SERVER["REMOTE_ADDR"]) . "',
                    `browser` = '" . mysql_real_escape_string(htmlentities($_SERVER["HTTP_USER_AGENT"])) . "',
                    `preg` = '1'
                ") or die('ERROR: Administrator setup</body></html>');
                $user_id = mysql_insert_id();
                // Устанавливаем сессию и COOKIE c данными администратора
                $_SESSION['uid'] = $user_id;
                $_SESSION['ups'] = md5(md5($admin_pass));
                setcookie("cuid", base64_encode($user_id), time() + 3600 * 24 * 365);
                setcookie("cups", md5($admin_pass), time() + 3600 * 24 * 365);
                // Установка ДЕМО данных
                if($demo){
                    $demo_data = new parse_sql('data/demo.sql');
                }
                // Установка завершена
                header('Location: index.php?act=install&mod=final&lng_id=' . $lng_id);
            }
        }
        echo '<form action="index.php?act=install&amp;mod=set&amp;lng_id=' . $lng_id . '" method="post">' .
            '<h2 class="blue">' . $lng['database'] . '</h2>' . show_errors($db_error) .
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
            echo '<p><h2 class="blue">' . $lng['site_settings'] . '</h2>' . show_errors($site_error) .
                '<small class="blue"><b>' . $lng['site_url'] . ':</b></small><br />' .
                '<input type="text" name="siteurl" value="' . $site_url . '"' . (isset($site_error['url']) ? ' style="background-color: #FFCCCC"' : '') . '><br />' .
                '<small class="gray">' . $lng['site_url_help'] . '</small><br />' .
                '<small class="blue"><b>' . $lng['site_email'] . ':</b></small><br />' .
                '<input type="text" name="sitemail" value="' . $site_mail . '"><br />' .
                '<small class="gray">' . $lng['site_email_help'] . '</small><br />' .
                '<input type="checkbox" name="demo" value="1"><small class="blue">&#160;<b>' . $lng['install_demo'] . '</b></small><br />' .
                '<small class="gray">' . $lng['install_demo_help'] . '</small></p>' .
                '<p><h2 class="blue">' . $lng['admin'] . '</h2>' . show_errors($admin_error) .
                '<small class="blue"><b>' . $lng['admin_login'] . ':</b></small><br />' .
                '<input type="text" name="admin" value="' . $admin_user . '"' . (isset($admin_error['admin']) ? ' style="background-color: #FFCCCC"' : '') . '><br />' .
                '<small class="gray">' . $lng['admin_login_help'] . '</small><br />' .
                '<small class="blue"><b>' . $lng['admin_password'] . ':</b></small><br />' .
                '<input type="text" name="password" value="' . $admin_pass . '"' . (isset($admin_error['pass']) ? ' style="background-color: #FFCCCC"' : '') . '><br />' .
                '<small class="gray">' . $lng['admin_password_help'] . '</small></p>' .
                '<p><input type="submit" name="install" value="' . $lng['setup'] . '"></p>';
        } else {
            echo '<p><input type="submit" name="check" value="' . $lng['check'] . '"></p>';
        }
        echo '</form>';
        echo '<p><a href="index.php?act=install&amp;mod=set&amp;lng_id=' . $lng_id . '">' . $lng['reset_form'] . '</a></p>';
        break;

    default:
        /*
        -----------------------------------------------------------------
        Проверка настроек PHP и прав доступа
        -----------------------------------------------------------------
        */
        echo '<h2 class="blue">' . $lng['check_settings'] . '</h2>';
        $folders = array (
            '/download/arctemp/',
            '/download/files/',
            '/download/graftemp/',
            '/download/screen/',
            '/files/cache/',
            '/files/forum/attach/',
            '/files/library/',
            '/files/users/album/',
            '/files/users/avatar/',
            '/files/users/photo/',
            '/files/users/pm/',
            '/gallery/foto/',
            '/gallery/temp/',
            '/incfiles/'
        );
        $files = array (
            '/library/java/textfile.txt',
            '/library/java/META-INF/MANIFEST.MF'
        );
        require('check.php');
        echo '<hr />';
        if (!empty($error_php) || !empty($error_rights_folders) || !empty($error_rights_files)) {
            echo '<p>' . $lng['critical_errors'] . '</p>' .
                '<p><a href="index.php?lng_id=' . $lng_id . '">&lt;&lt; ' . $lng['back'] . '</a> | ' .
                '<a href="index.php?act=install&amp;lng_id=' . $lng_id . '">' . $lng['check_again'] . '</a></p>';
        } elseif (!empty($warning)) {
            echo '<p>' . $lng['are_warnings'] . '</p>' .
                '<p><a href="index.php?lng_id=' . $lng_id . '">&lt;&lt; ' . $lng['back'] . '</a> | ' .
                '<a href="index.php?act=install&amp;lng_id=' . $lng_id . '">' . $lng['check_again'] . '</a></p>' .
                '<p>' . $lng['ignore_warnings'] . '</p>' .
                '<p><a href="index.php?act=install&amp;mod=set&amp;lng_id=' . $lng_id . '">' . $lng['start_installation'] . '</a> ' . $lng['not_recommended'] . '</p>';
        } else {
            echo '<p>' . $lng['configuration_successful'] . '</p>' .
                '<a href="index.php?lng_id=' . $lng_id . '">&lt;&lt; ' . $lng['back'] . '</a> | ' .
                '<a href="index.php?act=install&amp;mod=set&amp;lng_id=' . $lng_id . '">' . $lng['start_installation'] . ' &gt;&gt;</a>';
            echo '</p>';
        }
        break;
}
echo '</body></html>';
?>