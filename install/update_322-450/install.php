<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');
switch ($act) {
    case 'step2':
        /*
        -----------------------------------------------------------------
        Подготовка таблиц
        -----------------------------------------------------------------
        */
        echo '<span class="st">' . $lng['check_1'] . '</span>' .
             '<h2 class="green">' . $lng['prepare_tables'] . '</h2>' .
             '<span class="gray">' . $lng['data_conversion'] . '</span><br />' .
             '<span class="gray">' . $lng['photoalbums_move'] . '</span><br />' .
             '<span class="gray">' . $lng['final'] . '</span>' .
             '<hr />';
        if (!isset($_SESSION['step2'])) {
            $sql_errors = install::parse_sql(MODE . '/install.sql');
            if (!empty($sql_errors)) {
                echo '<span class="red">' . $lng['error_table_prepare'] . ':</span><br /><small>';
                foreach ($sql_errors as $val) echo $val . '<br />';
                echo '</small>';
            }
            $_SESSION['step2'] = 1;
        }
        echo '<h3><a href="index.php?act=step3">' . $lng['continue'] . '</a></h3>';
        break;

    case 'step3':
        /*
        -----------------------------------------------------------------
        Конвертация данных
        -----------------------------------------------------------------
        */
        echo '<div>' .
             '<span class="st">' . $lng['check_1'] . '</span><br />' .
             '<span class="st">' . $lng['prepare_tables'] . '</span>' .
             '<h2 class="green">' . $lng['data_conversion'] . '</h2>' .
             '<span class="gray">' . $lng['photoalbums_move'] . '</span><br />' .
             '<span class="gray">' . $lng['final'] . '</span>' .
             '</div><hr />';
        if (!isset($_SESSION['step3'])) {
            // Конвертируем Карму
            $req = mysql_query("SELECT `id`, `plus_minus` FROM `users`");
            while (($res = mysql_fetch_assoc($req)) !== false) {
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
            mysql_query("UPDATE `cms_settings` SET `val`='$language' WHERE `key`='lng'");
            $_SESSION['step3'] = 1;
        }
        echo '<h3><a href="index.php?act=step4">' . $lng['continue'] . '</a></h3>';
        break;

    case 'step4':
        /*
        -----------------------------------------------------------------
        Перенос Фотоальбомов
        -----------------------------------------------------------------
        */
        echo '<div>' .
             '<span class="st">' . $lng['check_1'] . '</span><br />' .
             '<span class="st">' . $lng['prepare_tables'] . '</span><br />' .
             '<span class="st">' . $lng['data_conversion'] . '</span>' .
             '<h2 class="green">' . $lng['photoalbums_move'] . '</h2>' .
             '<span class="gray">' . $lng['final'] . '</span>' .
             '</div><hr />';
        if (!isset($_SESSION['step4'])) {
            require('../incfiles/lib/class.upload.php');
            $req_a = mysql_query("SELECT * FROM `gallery` WHERE `type` = 'al' AND `user` = '1'");
            // Получаем список альбомов юзера
            while (($res_a = mysql_fetch_assoc($req_a)) !== false) {
                // Проверяем, есть ли юзер?
                $req_u = mysql_query("SELECT `id` FROM `users` WHERE `name` = '" . $res_a['avtor'] . "' LIMIT 1");
                if (mysql_num_rows($req_u)) {
                    // Если юзер есть, обрабатываем дальше
                    $file_list = array();
                    $res_u = mysql_fetch_assoc($req_u);
                    $req_f = mysql_query("SELECT * FROM `gallery` WHERE `type` = 'ft' AND `refid` = '" . $res_a['id'] . "'");
                    if (mysql_num_rows($req_f)) {
                        // Получаем список фотографий юзера
                        while (($res_f = mysql_fetch_assoc($req_f)) !== false) {
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
                            $handle->file_new_name_body = 'img_' . time();
                            $handle->allowed = array(
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
                                $handle->file_new_name_body = 'tmb_' . time();
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
                                                `time` = '" . time() . "',
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
            $_SESSION['step4'] = 1;
        }
        echo '<h3><a href="index.php?act=final">' . $lng['continue'] . '</a></h3>';
        break;

    case 'final':
        /*
        -----------------------------------------------------------------
        Обновление успешно завершено
        -----------------------------------------------------------------
        */
        if (!isset($_SESSION['updated'])) {
            install::parse_sql(MODE . '/install.sql');
            // Конвертируем IP адреса Форума
            $req = mysql_query("SELECT `id`, `ip_old` FROM `forum` WHERE `type` = 'm'");
            while (($res = mysql_fetch_assoc($req)) !== false) {
                if (!empty($res['ip_old']) && core::ip_valid($res['ip_old'])) {
                    mysql_query("UPDATE `forum` SET `ip` = '" . ip2long($res['ip_old']) . "' WHERE `id` = '" . $res['id'] . "' LIMIT 1");
                }
            }
            mysql_query("ALTER TABLE `forum` DROP `ip_old`");
        }
        $_SESSION['updated'] = 1;
        echo '<div>' .
             '<span class="st">' . $lng['check_1'] . '</span><br />' .
             '<span class="st">' . $lng['prepare_tables'] . '</span><br />' .
             '<span class="st">' . $lng['data_conversion'] . '</span><br />' .
             '<span class="st">' . $lng['photoalbums_move'] . '</span>' .
             '<h2 class="green">' . $lng['final'] . '</h2>' .
             '</div><hr />';
        echo '<p><h3 class="green">' . str_replace('INSTALL_VERSION', INSTALL_VERSION, $lng['successfully_updated']) . '</h3></p>' .
             '<p>' . $lng['final_note'] . '</p>' .
             '<hr /><h3><a href="' . $set['homeurl'] . '">' . $lng['go_to_site'] . '</a></h3>';
        break;

    default:
        /*
        -----------------------------------------------------------------
        Проверка настроек PHP и прав доступа
        -----------------------------------------------------------------
        */
        $search = array('#UPDATE_VERSION#', '#MODE#');
        $replace = array(UPDATE_VERSION, MODE);
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
        echo '</p><hr />';
        if ($php_errors || $folders || $files) {
            echo '<h3 class="red">' . $lng['critical_errors'] . '</h3>' .
                 '<h3><a href="index.php">' . $lng['check_again'] . '</a></h3>';
        } elseif ($php_warnings) {
            echo '<h3 class="red">' . $lng['are_warnings'] . '</h3>' .
                 '<h3><a href="index.php">' . $lng['check_again'] . '</a></h3>' .
                 '<a href="index.php?act=set">' . $lng['ignore_warnings'] . '</a>';
        } else {
            echo '<h3><a href="index.php?act=step2">' . $lng['start_update'] . '</a></h3>';
        }
}
?>