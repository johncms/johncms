<?

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

$mp = new mainpage();

/*
-----------------------------------------------------------------
Блок информации
-----------------------------------------------------------------
*/
echo '<div class="phdr"><b>' . $lng['information'] . '</b></div>';
echo $mp->news;
echo '<div class="menu"><a href="news/index.php">' . $lng['news_archive'] . '</a> (' . $mp->newscount . ')</div>' .
    '<div class="menu"><a href="pages/faq.php">' . $lng['information'] . ', FAQ</a></div>';

/*
-----------------------------------------------------------------
Блок общения
-----------------------------------------------------------------
*/
echo '<div class="phdr"><b>' . $lng['dialogue'] . '</b></div>';
// Ссылка на гостевую
if ($set['mod_guest'] || $rights >= 7)
    echo '<div class="menu"><a href="guestbook/index.php">' . $lng['guestbook'] . '</a> (' . functions::stat_guestbook() . ')</div>';
// Ссылка на Форум
if ($set['mod_forum'] || $rights >= 7)
    echo '<div class="menu"><a href="forum/">' . $lng['forum'] . '</a> (' . functions::stat_forum() . ')</div>';

/*
-----------------------------------------------------------------
Блок полезного
-----------------------------------------------------------------
*/    
echo '<div class="phdr"><b>' . $lng['useful'] . '</b></div>';
// Ссылка на загрузки
if ($set['mod_down'] || $rights >= 7)
    echo '<div class="menu"><a href="download/">' . $lng['downloads'] . '</a> (' . functions::stat_download() . ')</div>';
// Ссылка на библиотеку
if ($set['mod_lib'] || $rights >= 7)
    echo '<div class="menu"><a href="library/">' . $lng['library'] . '</a> (' . functions::stat_library() . ')</div>';
// Ссылка на библиотеку
if ($set['mod_gal'] || $rights >= 7)
    echo '<div class="menu"><a href="gallery/">' . $lng['gallery'] . '</a> (' . functions::stat_gallery() . ')</div>';
if ($user_id || $set['active']) {
    echo '<div class="phdr"><b>' . $lng['community'] . '</b></div>' .
        '<div class="menu"><a href="users/index.php">' . $lng['users'] . '</a> (' . functions::stat_users() . ')</div>' .
        '<div class="menu"><a href="users/album.php">' . $lng['photo_albums'] . '</a> (' . functions::count_photo() . ')</div>';
}
echo '<div class="phdr"><a href="http://gazenwagen.com">Gazenwagen</a></div>';
?>
