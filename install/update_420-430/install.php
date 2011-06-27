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
    case 'final':
        /*
        -----------------------------------------------------------------
        Проводим обновление
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
        echo '<p><h3 class="green">' . str_replace('INSTALL_VERSION', INSTALL_VERSION, $lng['successfully_updated']) . '</h3></p>' .
             '<p>' . $lng['final_note'] . '</p>' .
             '<hr /><h3><a href="' . $set['homeurl'] . '">' . $lng['go_to_site'] . '</a></h3>';
        break;

    default:
        $search = array('#UPDATE_VERSION#', '#MODE#');
        $replace = array(UPDATE_VERSION, MODE);
        echo str_replace($search, $replace, $lng['update_warning']);
        echo '<hr /><h3><a href="index.php?act=final">' . $lng['start_update'] . '</a></h3>';
}
?>