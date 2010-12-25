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
if ($install || $update)
    die('ERROR: installation of languages is impossible</body></html>');
switch ($_GET['mod']) {
    case 'install':
        $select = isset($_POST['select']) ? $_POST['select'] : false;
        if ($select) {
            $sys_init = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_lng_list` WHERE `iso` = '" . $core->language_iso . "'"), 0) ? false : true;
            echo '<h3 class="blue">' . $lng['install_languages'] . '</h3>';
            foreach ($select as $var) {
                $sys_update = false;
                $usr_update = false;
                $req = mysql_query("SELECT * FROM `cms_lng_list` WHERE `iso` = '" . $lng_set[$var]['iso'] . "' LIMIT 1");
                if (mysql_num_rows($req)) {
                    $res = mysql_fetch_assoc($req);
                    // Удаляем выбранный язык
                    mysql_query("DELETE FROM `cms_lng_phrases` WHERE `language_id` = '" . $res['id'] . "'");
                    mysql_query("DELETE FROM `cms_lng_list` WHERE `id` = '" . $res['id'] . "'");
                    mysql_query("OPTIMIZE TABLE `cms_lng_list` , `cms_lng_phrases`");
                    $lng_setup = $lng['updated'];
                    $usr_update = true;
                    if ($res['id'] == $core->language_id)
                        $sys_update = true;
                } else {
                    $lng_setup = $lng['installed'];
                }
                /*
                -----------------------------------------------------------------
                Добавляем в базу выбранный язык
                -----------------------------------------------------------------
                */
                $attr = serialize(array (
                    'author' => $lng_set[$var]['author'],
                    'author_email' => $lng_set[$var]['author_email'],
                    'author_url' => $lng_set[$var]['author_url'],
                    'description' => $lng_set[$var]['description'],
                    'version' => $lng_set[$var]['version']
                ));
                // Добавляем в список
                mysql_query("INSERT INTO `cms_lng_list` SET
                    `iso` = '" . $lng_set[$var]['iso'] . "',
                    `name` = '" . $lng_set[$var]['name'] . "',
                    `build` = '" . $lng_set[$var]['build'] . "',
                    `attr` = '" . mysql_real_escape_string($attr) . "'
                ");
                $language_id = mysql_insert_id();
                // Добавляем фразы
                $lng_array = parse_ini_file('languages/' . $lng_set[$var]['filename'] . '.ini', true);
                unset($lng_array['description']); // Удаляем описание языка
                unset($lng_array['install']);     // Удаляем фразы инсталлятора
                foreach ($lng_array as $module => $phr_array) {
                    foreach ($phr_array as $keyword => $phrase) {
                        mysql_query("INSERT INTO `cms_lng_phrases` SET
                            `language_id` = '$language_id',
                            `module` = '" . mysql_real_escape_string($module) . "',
                            `keyword` = '" . mysql_real_escape_string($keyword) . "',
                            `default` = '" . mysql_real_escape_string($phrase) . "'
                        ");
                    }
                }
                // Инициализируем системные настройки
                if ($sys_init && $lng_set[$var]['iso'] == $core->language_iso) {
                    mysql_query("UPDATE `users` SET `set_language` = '0'");
                }
                // Обновляем системные настройки
                if ($sys_update || $sys_init)
                    mysql_query("UPDATE `cms_settings` SET `val` = '" . $language_id . "' WHERE `key` = 'lng_id'");
                // Обновляем пользовательские настройки
                if ($usr_update)
                    mysql_query("UPDATE `users` SET `set_language` = '" . $language_id . "' WHERE `set_language` = '" . $res['id'] . "'");
                echo '<div>&#160;<span class="green"><b>' . $lng_setup . '</b></span>&#160;' . $lng_set[$var]['name'] . '</div>';
            }
            echo '<p><a href="index.php?act=languages&amp;lng_id=' . $lng_id . '">' . $lng['back'] . '</a><br />' .
                '<a href="index.php?lng_id=' . $lng_id . '">' . $lng['home'] . '</a></p>';
        } else {
            echo $lng['error_select_language'];
            echo '<form action="index.php">' .
                '<input type="hidden" name="act" value="languages">' .
                '<input type="hidden" name="lng_id" value="' . $lng_id . '">' .
                '<p><input type="submit" name="submit" value="' . $lng['back'] . '"></p>' .
                '</form>';
        }
        break;
        default:
        /*
        -----------------------------------------------------------------
        Выбор языков
        -----------------------------------------------------------------
        */
        $req = mysql_query("SELECT * FROM `cms_lng_list`");
        $lng_array = array ();
        while ($res = mysql_fetch_assoc($req)) {
            $lng_array[$res['iso']] = $res['build'];
        }
        echo '<form action="index.php?act=languages&amp;mod=install" method="post"><table>' .
            '<tr><td>&nbsp;</td><td style="padding-bottom:4px"><h3 class="blue">' . $lng['select_languages_to_install'] . '</h3><small>' . $lng['language_install_note'] . '</small></td></tr>';
        foreach ($lng_set as $key => $val) {
            $inst_status = array_key_exists($val['iso'], $lng_array) ? '<span class="green">[' . $lng['installed'] . ']</span>' : false;
            $update_status = array_key_exists($val['iso'], $lng_array) && $val['build'] > $lng_array[$val['iso']] ? ' <small class="red">[' . $lng['update_available'] . ']</small>' : false;
            $sys_status = $core->language_iso == $val['iso'] ? ' <span class="blue">[' . $lng['system'] . ']</span>' : false;
            $lng_menu = array (
                ($inst_status || $sys_status ? '<span class="gray">' . $lng['status'] . ':</span> ' . $inst_status . ' ' . $sys_status : ''),
                (!empty($val['author']) ? '<span class="gray">' . $lng['author'] . ':</span> ' . $val['author'] : ''),
                (!empty($val['author_email']) ? '<span class="gray">E-mail:</span> ' . $val['author_email'] : ''),
                (!empty($val['author_url']) ? '<span class="gray">URL:</span> ' . $val['author_url'] : ''),
                (!empty($val['description']) ? '<span class="gray">' . $lng['description'] . ':</span> ' . $val['description'] : '')
            );
            echo '<tr>' .
                '<td valign="top"><input type="checkbox" name="select[]" value="' . $key . '"' . (!$inst_status && $sys_status ? ' disabled="disabled" checked="checked"' : ($update_status ? ' checked="checked"' : '')) . '/>' . (!$inst_status && $sys_status ? '<input type="hidden" name="select[]" value="' . $key . '">' : '') . '</td>' .
                '<td style="padding-bottom:6px"><b>' . $val['name'] . '</b>&#160;<span class="blue">[' . $val['iso'] . ']</span>' . $update_status . (!empty($lng_menu) ? '<br /><small>' . functions::display_menu($lng_menu, '<br />') . '</small>' : '') . '</td>' .
                '</tr>';
        }
        echo '<tr><td>&nbsp;</td><td style="padding-top:6px"><input type="submit" name="submit" value="' . $lng['install'] . '" /></td></tr>' .
            '</table>' .
            '<input type="hidden" name="lng_id" value="' . $lng_id . '" />' .
            '</form>' .
            '<a href="index.php?lng_id=' . $lng_id . '">' . $lng['home'] . '</a>';
}
?>