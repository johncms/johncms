<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

// Рекламный блок сайта
if (!empty($cms_ads[2])) {
    echo '<div class="gmenu">' . $cms_ads[2] . '</div>';
}

echo '</div><div class="fmenu">';

if (isset($_GET['err']) || $headmod != "mainpage" || ($headmod == 'mainpage' && isset($_GET['act']))) {
    echo '<div><a href=\'' . $set['homeurl'] . '\'>' . functions::image('menu_home.png') . _t('Home', 'system') . '</a></div>';
}

echo '<div>' . counters::online() . '</div>' .
    '</div>' .
    '<div style="text-align:center">' .
    '<p><b>' . $set['copyright'] . '</b></p>';

// Счетчики каталогов
functions::display_counters();

// Рекламный блок сайта
if (!empty($cms_ads[3])) {
    echo '<br />' . $cms_ads[3];
}

/*
-----------------------------------------------------------------
ВНИМАНИЕ!!!
Данный копирайт нельзя убирать в течение 90 дней с момента установки скриптов
-----------------------------------------------------------------
ATTENTION!!!
The copyright could not be removed within 90 days of installation scripts
-----------------------------------------------------------------
*/
echo '<div><small>&copy; <a href="http://johncms.com">JohnCMS</a></small></div>' .
    '</div></body></html>';
