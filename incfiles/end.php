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

defined('_IN_JOHNCMS') or die('Error: restricted access');

// Рекламный блок MOBILEADS.RU
$mad_siteid = 0;
if ($mad_siteid) {
    if (isset($_SESSION['mad_links']) && $_SESSION['mad_time'] > ($realtime - 60 * 15))
        echo '<div class="gmenu">' . $_SESSION['mad_links'] . '</div>';
    else
        echo '<div class="gmenu">' . mobileads($mad_siteid) . '</div>';
}

// Рекламный блок сайта
if (!empty($cms_ads[2]))
    echo '<div class="gmenu">' . $cms_ads[2] . '</div>';
echo '</div><div class="fmenu">';
if ($headmod != "mainpage" || ($headmod == 'mainpage' && $act))
    echo '<a href=\'' . $set['homeurl'] . '\'>' . $lng['homepage'] . '</a><br/>';

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
echo '</div><div class="footer">' . functions::stat_online() . '</div>';

////////////////////////////////////////////////////////////
// Выводим информацию внизу страницы                      //
////////////////////////////////////////////////////////////
echo '<div style="text-align:center">';
echo '<p><b>' . $set['copyright'] . '</b></p>';
if (!$user_id || ($user_id && $set_user['gzip']))
    functions::stat_gzip();                    // Индикатор сжатия
if (!$user_id || ($user_id && $set_user['online']))
    functions::stat_timeonline();                  // Время, проведенное в онлайне
if (!$user_id || ($user_id && $set_user['movings']))
    echo $lng['transitions'] . ': ' . $movings; // Счетчик перемещений по сайту
functions::display_counters();                        // Счетчики каталогов

// Рекламный блок сайта
if (!empty($cms_ads[3]))
    echo '<br />' . $cms_ads[3];

////////////////////////////////////////////////////////////
// ВНИМАНИЕ!!!                                            //
// Данный копирайт нельзя убирать в течение 60 дней       //
// с момента установки скриптов                           //
////////////////////////////////////////////////////////////
echo '<div><small><a href="http://johncms.com">JohnCMS</a></small></div>';
echo '</div></body></html>';

?>