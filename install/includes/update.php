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
switch ($mod) {
    case 'step1':
        /*
        -----------------------------------------------------------------
        Проверка настроек PHP и прав доступа
        -----------------------------------------------------------------
        */
        echo '<ul class="gray"><li><h3 class="blue">' . $lng['check_settings'] . '</h3></li><li>' . $lng['prepare_tables'] . '</li><li>' . $lng['data_conversion'] . '</li><li>' . $lng['photoalbums_move'] . '</li></ul><hr />';
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
            '/gallery/temp/'
        );
        $files = array (
            '/library/java/textfile.txt',
            '/library/java/META-INF/MANIFEST.MF',
            '/incfiles/db.php'
        );
        require('check.php');
        if (!empty($error_php) || !empty($error_rights_folders) || !empty($error_rights_files)) {
            // Если есть критические ошибки
            echo '<p class="red">' . $lng['critical_errors'] . '</p>' .
                '<p><a href="index.php?act=update&amp;mod=step1&amp;lng_id=' . $lng_id . '">' . $lng['check_again'] . '</a></p>';
        } elseif (!empty($warning)) {
            // Если есть предупреждения
            echo '<p class="red">' . $lng['are_warnings'] . '</p>' .
                '<p><a href="index.php?act=update&amp;mod=step1&amp;lng_id=' . $lng_id . '">' . $lng['check_again'] . '</a></p>' .
                '<p>' . $lng['ignore_warnings'] . '</p>' .
                '<p><a href="index.php?act=update&amp;mod=step2&amp;lng_id=' . $lng_id . '">' . $lng['start_update'] . '</a> ' . $lng['not_recommended'] . '</p>';
        } else {
            // Если проверка завершилась удачно
            echo '<p class="green">' . $lng['configuration_successful'] . '<br /><a href="index.php?act=update&amp;mod=step2&amp;lng_id=' . $lng_id . '">' . $lng['start_update'] . '</a></p>';
        }
        break;

    case 'step2':
        /*
        -----------------------------------------------------------------
        Подготовка таблиц
        -----------------------------------------------------------------
        */
        echo '<ul class="gray"><li>' . $lng['check_settings'] . '</li><li><h3 class="blue">' . $lng['prepare_tables'] . '</h3></li><li>' . $lng['data_conversion'] . '</li><li>' . $lng['photoalbums_move'] . '</li></ul><hr />';
        require('includes/parse_sql.php');
        $sql = new parse_sql('data/update.sql');
        if (!empty($sql->errors)) {
            echo '<span class="red">' . $lng['error_table_prepare'] . ':</span><br /><small>';
            foreach ($sql->errors as $val)echo $val . '<br />';
            echo '</small>';
        }
        echo '<p class="green">' . $lng['table_prepared'] . '<br /><a href="index.php?act=update&amp;mod=step3&amp;lng_id=' . $lng_id . '">' . $lng['continue'] . '</a></p>';
        break;

    case 'step3':
        /*
        -----------------------------------------------------------------
        Конвертация данных
        -----------------------------------------------------------------
        */
        echo '<ul class="gray"><li>' . $lng['check_settings'] . '</li><li>' . $lng['prepare_tables'] . '</li><li><h3 class="blue">' . $lng['data_conversion'] . '</h3></li><li>' . $lng['photoalbums_move'] . '</li></ul><hr />';

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
        
        // Обновляем настройки
        mysql_query("UPDATE `cms_settings` SET `val`='$lng_insert_id' WHERE `key`='lng_id'");
        mysql_query("UPDATE `cms_settings` SET `val`='" . $lng_set[$lng_id]['iso'] . "' WHERE `key`='lng_iso'");

        // Конвертируем Карму
        $req = mysql_query("SELECT `id`, `plus_minus` FROM `users`");
        while ($res = mysql_fetch_assoc($req)) {
            $karma = explode('|', $res['plus_minus']);
            $karma_plus = $karma[0] ? $karma[0] : '0';
            $karma_minus = $karma[1] ? $karma[1] : '0';
            mysql_query("UPDATE `users` SET
                `karma_plus` = '$karma_plus',
                `karma_minus` = '$karma_minus'
                WHERE `id` = '" . $res['id'] . "'
            ");
        }
        mysql_query("ALTER TABLE `users` DROP `karma`");
        mysql_query("ALTER TABLE `users` DROP `plus_minus`");
        mysql_query("OPTIMIZE TABLE `users`");
        echo '<p class="green">' . $lng['data_converted'] . '<br /><a href="index.php?act=update&amp;mod=step4&amp;lng_id=' . $lng_id . '">' . $lng['continue'] . '</a></p>';
        break;

    case 'step4':
        /*
        -----------------------------------------------------------------
        Перенос Фотоальбомов
        -----------------------------------------------------------------
        */
        echo '<ul class="gray"><li>' . $lng['check_settings'] . '</li><li>' . $lng['prepare_tables'] . '</li><li>' . $lng['data_conversion'] . '</li><li><h3 class="blue">' . $lng['photoalbums_move'] . '</h3></li></ul><hr />';
        require('../incfiles/lib/class.upload.php');
        $req_a = mysql_query("SELECT * FROM `gallery` WHERE `type` = 'al' AND `user` = '1'");
        // Получаем список альбомов юзера
        while ($res_a = mysql_fetch_assoc($req_a)) {
            // Проверяем, есть ли юзер?
            $req_u = mysql_query("SELECT `id` FROM `users` WHERE `name` = '" . $res_a['avtor'] . "' LIMIT 1");
            if (mysql_num_rows($req_u)) {
                // Если юзер есть, обрабатываем дальше
                $file_list = array ();
                $res_u = mysql_fetch_assoc($req_u);
                $req_f = mysql_query("SELECT * FROM `gallery` WHERE `type` = 'ft' AND `refid` = '" . $res_a['id'] . "'");
                if (mysql_num_rows($req_f)) {
                    // Получаем список фотографий юзера
                    while ($res_f = mysql_fetch_assoc($req_f)) {
                        if (file_exists('../gallery/foto/' . $res_f['name'])) {
                            $file_list[] = $res_f['name'];
                        }
                    }
                }
                // Если есть файлы, конвертируем их
                if (count($file_list)) {
                    // Создаем альбом
                    mysql_query("INSERT INTO `cms_album_cat` SET
                        `user_id` = '" . $res_u['id'] . "',
                        `sort` = '1',
                        `name` = 'Old Album',
                        `description` = 'It is transferred from old Gallery',
                        `access` = '4'
                    ");
                    $al = mysql_insert_id();
                    foreach ($file_list as $file) {
                        $handle = new upload('../gallery/foto/' . $file);
                        $handle->file_new_name_body = 'img_' . $realtime;
                        $handle->allowed = array (
                            'image/jpeg',
                            'image/gif',
                            'image/png'
                        );
                        $handle->image_resize = true;
                        $handle->image_x = 640;
                        $handle->image_y = 480;
                        $handle->image_ratio_no_zoom_in = true;
                        $handle->image_convert = 'jpg';
                        $handle->process('../files/users/album/' . $res_u['id'] . '/');
                        $img_name = $handle->file_dst_name;
                        if ($handle->processed) {
                            // Обрабатываем превьюшку
                            $handle->file_new_name_body = 'tmb_' . $realtime;
                            $handle->image_resize = true;
                            $handle->image_x = 80;
                            $handle->image_y = 80;
                            $handle->image_ratio_no_zoom_in = true;
                            $handle->image_convert = 'jpg';
                            $handle->process('../files/users/album/' . $res_u['id'] . '/');
                            $tmb_name = $handle->file_dst_name;
                            if ($handle->processed) {
                                mysql_query("INSERT INTO `cms_album_files` SET
                                    `album_id` = '$al',
                                    `user_id` = '" . $res_u['id'] . "',
                                    `img_name` = '" . mysql_real_escape_string($img_name) . "',
                                    `tmb_name` = '" . mysql_real_escape_string($tmb_name) . "',
                                    `time` = '$realtime',
                                    `access` = '4'
                                ");
                            }
                        }
                        $handle->clean();
                        unset($handle);
                    }
                    mysql_query("DELETE FROM `gallery` WHERE `refid` = '" . $res_a['id'] . "'");
                }
            }
        }
        echo '<p class="green">' . $lng['photoalbums_moved'] . '<br /><a href="index.php?act=update&amp;mod=final&amp;lng_id=' . $lng_id . '">' . $lng['continue'] . '</a></p>';
        break;

    case 'final':
        /*
        -----------------------------------------------------------------
        Обновление успешно завершено
        -----------------------------------------------------------------
        */
        // Пересоздаем системный файл db.php
        require('../incfiles/db.php');
        $dbfile = "<?php\r\n\r\n" .
            "defined('_IN_JOHNCMS') or die ('Error: restricted access');\r\n\r\n" .
            '$db_host = ' . "'$db_host';\r\n" .
            '$db_name = ' . "'$db_name';\r\n" .
            '$db_user = ' . "'$db_user';\r\n" .
            '$db_pass = ' . "'$db_pass';\r\n\r\n" .
            '$system_build = ' . "'$system_build';\r\n\r\n" .
            '?>';
        if (!file_put_contents('../incfiles/db.php', $dbfile)) {
            echo 'ERROR: Can not write db.php</body></html>';
            exit;
        }
        echo '<h2 class="blue">' . $lng['site_updated'] . '</h2>' . $lng['final_note'];
        echo '<p><a href="index.php">Установить дополнительные языки</a><br /><a href="' . $set['homeurl'] . '">Перейти на Сайт</a></p>';
        break;

    default:
        echo $lng['update_warning'];
        echo '<br /><a href="index.php?act=update&amp;mod=step1&amp;lng_id=' . $lng_id . '">' . $lng['start_update'] . '</a>';
}
?>