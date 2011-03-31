<?php
header((stristr($agn, "msie") && stristr($agn, "windows")) ? 'Content-type: text/html; charset=UTF-8' : 'Content-type: application/xhtml+xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="utf-8"?>' .
    '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">' .
    '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">' .
    '<head><meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8"/>' .
    '<title>Language TEST</title>' .
    '<style type="text/css">' .
    'body {font-family: Arial, Helvetica, sans-serif; font-size: small; color: #000000; background-color: #FFFFFF}' .
    'h3{margin: 0; padding: 2px;}' .
    'ul{margin:0; padding-left:20px; }' .
    'li{padding-bottom: 6px; }' .
    '.red{color: #FF0000;}' .
    '.green{color: #009933;}' .
    '.blue{color: #0000EE;}' .
    '.gray{color: #999999;}' .
    '.small{font-size: x-small}' .
    '</style>' .
    '</head><body><div>';

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
Переключаем язык
-----------------------------------------------------------------
*/
$lng_id = 1;
if (isset($_REQUEST['lng_id']) && in_array($_REQUEST['lng_id'], $lng_key)) {
    $lng_id = intval($_REQUEST['lng_id']);
}
echo '<h3>Select language</h3>';
echo '<form action="test.php" method="post">';
foreach ($lng_set as $key => $val) {
    echo '<input type="radio" name="lng_id" value="' . $key . '" ' . ($key == $lng_id ? 'checked="checked"' : '') . ' /> ' . $val['name'] . (isset($language) && !empty($language) && $language == $val['iso'] ? ' <small class="red">[' . $lng['system'] . ']</small>' : '') . '<br />';
}
echo '<p><input type="submit" name="submit" value="Select" /></p>';
echo '</form>';

/*
-----------------------------------------------------------------
Показываем таблицы с фразами выбранного языка
-----------------------------------------------------------------
*/
$lng_array = parse_ini_file('languages/' . $lng_set[$lng_id]['filename'] . '.ini', true);
foreach($lng_array as $key => $val){
    echo '<table width="790" border="1" cellpadding="1">';
    echo '<tr bgcolor="#CCCCCC"><td colspan="2"><h3>' . $key . '</h3></td></tr>';
    echo '<tr bgcolor="#DDDDDD" class="small"><td width="100"><b>Key</b></td><td><b>Value</b></td></tr>';
    foreach($val as $keyword => $phrase){
        echo '<tr class="small"><td width="150" valign="top">' . $keyword . '</td><td>' . $phrase . '</td></tr>';
    }
    echo '</table><br />';
}

echo '</div></body></html>';
?>