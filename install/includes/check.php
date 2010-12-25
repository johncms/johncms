<?php
defined('INSTALL') or die('Error: restricted access');
$error_php = array ();
$error_rights_folders = array ();
$error_rights_files = array ();
$warning = array ();
echo '<h2 class="blue">' . $lng['check_settings'] . '</h2>';

/*
-----------------------------------------------------------------
Проверка настроек PHP
-----------------------------------------------------------------
*/
if (version_compare(phpversion(), '5.1.0', '<'))
    $error_php[] = '<b>PHP ' . phpversion() . '</b> ' . $lng['error_php_version'];
// Проверка register_globals
if (ini_get('register_globals'))
    $warning[] = '<b class="red">register_globals ON</b> ' . $lng['warning_rerister_globals'];
// Проверка arg_separator.output
if (ini_get('arg_separator.output') != '&amp;')
    $warning[] = '<b class="red">arg_separator.output &quot;' . htmlentities(ini_get('arg_separator.output')) . '&quot;</b> ' . $lng['warning_arg_separator'];
// Проверка загрузки расширения MySQL
if (!extension_loaded('mysql'))
    $error_php[] = '[<b>mysql</b>] ' . $lng['error_module'];
// Проверка загрузки расширения gd
if (!extension_loaded('gd'))
    $error_php[] = '[<b>gd</b>] ' . $lng['error_module'];
// Проверка загрузки расширения zlib
if (!extension_loaded('zlib'))
    $error_php[] = '[<b>zlib</b>] ' . $lng['error_module'];
// Проверка загрузки расширения zlib
if (!extension_loaded('iconv'))
    $error_php[] = '[<b>iconv</b>] ' . $lng['error_module'];
// Проверка загрузки расширения mbstring
if (!extension_loaded('mbstring'))
    $error_php[] = '[<b>mbstring</b>] ' . $lng['error_module'];

// Проверка прав доступа к папкам
foreach ($folders as $val) {
    if ((@decoct(@fileperms('..' . $val)) % 1000) < 777) {
        $error_rights_folders[] = $val;
    }
}

// Проверка прав доступа к файлам
foreach ($files as $val) {
    if ((@decoct(@fileperms('..' . $val)) % 1000) < 666)
        $error_rights_files[] = $val;
}

/*
-----------------------------------------------------------------
Показываем критические ошибки PHP
-----------------------------------------------------------------
*/
if (!empty($error_php)) {
    echo '<h3>' . $lng['errors'] . '</h3><ul>';
    foreach ($error_php as $val) {
        echo '<li><span class="red">' . $val . '</span></li>';
    }
    echo '</ul>';
}

/*
-----------------------------------------------------------------
Показываем ошибки прав доступа к папкам
-----------------------------------------------------------------
*/
if (!empty($error_rights_folders)) {
    echo '<h3>' . $lng['error_access_rights'] . ' 777</h3><ul>';
    foreach ($error_rights_folders as $val) {
        echo '<li><span class="red">' . $val . '</span></li>';
    }
    echo '</ul>';
}

/*
-----------------------------------------------------------------
Показываем ошибки прав доступа к файлам
-----------------------------------------------------------------
*/
if (!empty($error_rights_files)) {
    echo '<h3>' . $lng['error_access_rights'] . ' 666</h3><ul>';
    foreach ($error_rights_files as $val) {
        echo '<li><span class="red">' . $val . '</span></li>';
    }
    echo '</ul>';
}

/*
-----------------------------------------------------------------
Показываем предупреждения
-----------------------------------------------------------------
*/
if (!empty($warning)) {
    echo '<h3>' . $lng['warnings'] . '</h3><ul>';
    foreach ($warning as $val) {
        echo '<li>' . $val . '</li>';
    }
    echo '</ul>';
}

?>