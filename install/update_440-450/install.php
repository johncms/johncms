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
    case'check':
        /*
        -----------------------------------------------------------------
        Проверка настроек PHP и прав доступа
        -----------------------------------------------------------------
        */
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
            echo'<form action="index.php?act=final" method="post"><p><input type="submit" value="' . $lng['continue'] . '"/></p></form>';
        }
        break;

    case 'final':
        /*
        -----------------------------------------------------------------
        Проводим обновление
        -----------------------------------------------------------------
        */
        if (!isset($_SESSION['updated'])) {
            install::parse_sql(MODE . '/install.sql');
        }
        $_SESSION['updated'] = 1;
        echo'<p><h3 class="green">' . str_replace('INSTALL_VERSION', INSTALL_VERSION, $lng['successfully_updated']) . '</h3></p>' .
            '<p>' . $lng['final_note'] . '</p>' .
            '<hr /><h3><a href="' . $set['homeurl'] . '">' . $lng['go_to_site'] . '</a></h3>';
        break;

    default:
        $search = array('#UPDATE_VERSION#', '#MODE#');
        $replace = array(UPDATE_VERSION, MODE);
        echo str_replace($search, $replace, $lng['update_warning']);
        echo'<form action="index.php?act=check" method="post"><p><input type="submit" value="' . $lng['start_update'] . '"/></p></form>';
}