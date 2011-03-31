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

define('INSTALL', 1);
define('_IN_JOHNCMS', 1);
@ini_set("max_execution_time", "600");

// Служебные переменные
$install = false;
$update = false;
$lng_install = false;
$lng_id = 1;
$system_build = 710; // Версия системы

/*
-----------------------------------------------------------------
Проверка, инсталлирована система, или нет
-----------------------------------------------------------------
*/
if (file_exists('../incfiles/db.php') && file_exists('../incfiles/core.php')) {
    // Если система инсталлирована
    require('../incfiles/core.php');
    if (!$core->system_build)
        $update = true;
} else {
    // Если система не инсталлирована
    $act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : '';
    $mod = isset($_REQUEST['mod']) ? trim($_REQUEST['mod']) : '';
    $install = true;
    session_name('SESID');
    session_start();
}

/*
-----------------------------------------------------------------
Получаем список доступных языков
-----------------------------------------------------------------
*/
$i = 1;
foreach (glob('languages/*.ini') as $file) {
    $ini = parse_ini_file($file, true);
    $lng_key[$ini['description']['iso']] = $i;
    $lng_set[$i] = $ini['description'];
    $lng_phrases[$i] = $ini['install'];
    unset($ini);
    ++$i;
}
if (!count($lng_key))
    die('ERROR: there are no languages for installation');

/*
-----------------------------------------------------------------
Переключаем язык интерфейса Инсталлятора
-----------------------------------------------------------------
*/
if (isset($_REQUEST['lng_id']) && in_array($_REQUEST['lng_id'], $lng_key)) {
    // Меняем язык по запросу из формы
    $lng_id = intval($_REQUEST['lng_id']);
}  elseif (isset($core->language_iso) && array_key_exists($core->language_iso, $lng_key)) {
    // Если система проинсталлирована, то используем ее язык
    $lng_id = $lng_key[$core->language_iso];
}  elseif (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    // Устанавливаем язык по браузеру
    $browser_lang = explode(',', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
    foreach ($browser_lang as $var) {
        $lang = substr($var, 0, 2);
        if (array_key_exists($lang, $lng_key)) {
            $lng_id = $lng_key[$lang];
            break;
        }
    }
}
$lng = $lng_phrases[$lng_id];

/*
-----------------------------------------------------------------
HTML Пролог и заголовки страниц
-----------------------------------------------------------------
*/
switch ($act) {
    case 'install':
        $pagetitle = $lng['install'];
        $pagedesc = '';
        break;

    case 'update':
        $pagetitle = $lng['update'];
        $pagedesc = 'Обновление с версии 3.2.2';
        break;

    case 'languages':
        $pagetitle = $lng['install_languages'];
        $pagedesc = '';
        break;

    default:
        $pagetitle = $lng['install'];
        $pagedesc = false;
}
ob_start();
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' .
    '<html xmlns="http://www.w3.org/1999/xhtml">' .
    '<title>JohnCMS 4.1.0 - ' . $pagetitle . '</title>' .
    '<style type="text/css">' .
    'body {font-family: Arial, Helvetica, sans-serif; font-size: small; color: #000000; background-color: #FFFFFF}' .
    'h2{margin: 0; padding: 0; padding-bottom: 4px;}' .
    'h3{margin: 0; padding: 0; padding-bottom: 2px;}' .
    'ul{margin:0; padding-left:20px; }' .
    'li{padding-bottom: 6px; }' .
    '.red{color: #FF0000;}' .
    '.green{color: #009933;}' .
    '.blue{color: #0000EE;}' .
    '.gray{color: #888888;}' .
    '.small{font-size: x-small}' .
    '</style>' .
    '</head><body>' .
    '<h2 class="green">JohnCMS 4.1.0</h2>' . $pagedesc . '<hr />';

/*
-----------------------------------------------------------------
Переключаем режимы работы
-----------------------------------------------------------------
*/
$actions = array (
    'install',
    'update',
    'languages'
);
if (in_array($act, $actions) && file_exists('includes/' . $act . '.php')) {
    require_once('includes/' . $act . '.php');
} else {
    /*
    -----------------------------------------------------------------
    Главное меню инсталлятора
    -----------------------------------------------------------------
    */
    echo '<form action="index.php" method="post">' .
        '<table>' .
        '<tr><td valign="top"><input type="radio" name="act" value="install" ' . ($install ? 'checked="checked"' : 'disabled="disabled"') . '/></td><td style="padding-bottom:6px"><h3 class="' . ($install ? 'blue' : 'gray') . '">'
        . $lng['install'] . '</h3><small>'
        . ($install ? $lng['install_note'] : '<span class="gray">' . $lng['alredy_installed'] . '</span>') . '</small></td></tr>' .
        '<tr><td valign="top"><input type="radio" name="act" value="update" ' . ($update ? 'checked="checked"' : 'disabled="disabled"') . '/></td><td style="padding-bottom:6px"><h3 class="' . ($update ? 'blue' : 'gray') . '">'
        . $lng['update'] . '</h3><small>'
        . ($update ? $lng['update_note'] : '<span class="gray">' . $lng['update_not_required'] . '</span>') . '</small></td></tr>' .
        '<tr><td valign="top"><input type="radio" name="act" value="languages" ' . (!$install && !$update ? 'checked="checked"' : 'disabled="disabled"') . '/></td><td style="padding-bottom:6px"><h3 class="'
        . (!$install && !$update ? 'blue' : 'gray') . '">' . $lng['install_languages'] . '</h3><small>'
        . (!$install && !$update ? $lng['install_languages_note'] : '<span class="gray">' . $lng['install_languages_impossible'] . '</span>') . '</small></td></tr>' .
        '<tr><td>&nbsp;</td><td><input type="submit" name="submit" value="' . $lng['continue'] . '" /></td></tr>' .
        '</table>' .
        '<input type="hidden" name="lng_id" value="' . $lng_id . '" />' .
        '</form><hr />' .
        '<form action="index.php" method="post"><table>' .
        '<tr><td>&nbsp;</td><td><h3>' . $lng['change_language'] . '</h3></td></tr>';
    foreach ($lng_set as $key => $val) {
        echo '<tr>' .
            '<td valign="top"><input type="radio" name="lng_id" value="' . $key . '" ' . ($key == $lng_id ? 'checked="checked"' : '') . ' /></td>' .
            '<td>' . $val['name'] . (isset($core->language_iso) && $core->language_iso == $val['iso'] ? ' <small class="red">[' . $lng['system'] . ']</small>' : '') . '</td>' .
            '</tr>';
    }
    echo '<tr><td>&nbsp;</td><td style="padding-top:6px"><input type="submit" name="submit" value="' . $lng['change'] . '" /></td></tr>' .
        '</table></form>';
}
echo '<hr />&copy;&#160;Powered by <a href="http://johncms.com">JohnCMS</a></body></html>';
?>