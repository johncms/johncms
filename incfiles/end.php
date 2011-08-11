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

// Рекламный блок сайта
if (!empty($cms_ads[2]))
    echo '<div class="gmenu">' . $cms_ads[2] . '</div>';

echo '</div><div class="fmenu">';
if ($headmod != "mainpage" || ($headmod == 'mainpage' && $act))
    echo '<a href="' . $set['homeurl'] . '">' . $lng['homepage'] . '</a><br/>';

// Меню быстрого перехода
if ($set_user['quick_go']) {
    echo '<form action="' . $set['homeurl'] . '/go.php" method="post">';
    echo '<div><select name="adres" style="font-size:x-small">
    <option selected="selected">' . $lng['quick_jump'] . '</option>
    <option value="guest">' . $lng['guestbook'] . '</option>
    <option value="forum">' . $lng['forum'] . '</option>
    <option value="news">' . $lng['news'] . '</option>
    <option value="gallery">' . $lng['gallery'] . '</option>
    <option value="down">' . $lng['downloads'] . '</option>
    <option value="lib">' . $lng['library'] . '</option>
    <option value="gazen">Gazenwagen :)</option>
    </select><input type="submit" value="Go!" style="font-size:x-small"/>';
    echo '</div></form>';
}
// Счетчик посетителей онлайн
echo '</div><div class="footer">' . counters::online() . '</div>';
echo '<div style="text-align:center">';
echo '<p><b>' . $set['copyright'] . '</b></p>';

// Счетчики каталогов
functions::display_counters();

// Рекламный блок сайта
if (!empty($cms_ads[3]))
    echo '<br />' . $cms_ads[3];

/*
-----------------------------------------------------------------
ВНИМАНИЕ!!!
Данный копирайт нельзя убирать в течение 60 дней с момента установки скриптов
-----------------------------------------------------------------
ATTENTION!!!
The copyright could not be removed within 60 days of installation scripts
-----------------------------------------------------------------
*/
echo '<div><small>&copy; <a href="http://johncms.com">JohnCMS</a></small></div>';

echo '</div></body></html>';