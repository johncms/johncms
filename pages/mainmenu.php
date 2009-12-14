<?

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

////////////////////////////////////////////////////////////
// Главное меню сайта                                     //
////////////////////////////////////////////////////////////
require_once ('incfiles/class_mainpage.php');
$mp = new mainpage();
// Блок новостей
echo $mp->news;
echo '<div class="bmenu">Информация</div>';
echo '<div class="menu"><a href="str/news.php">Архив новостей</a> (' . $mp->newscount . ')</div>';
echo '<div class="menu"><a href="index.php?act=info">Доп. информация</a></div>';
echo '<div class="menu"><a href="index.php?act=users">Актив Сайта</a></div>';
echo '<div class="bmenu">Общение</div>';
echo '<div class="menu"><a href="str/guest.php">Гостевая</a> (' . gbook() . ')</div>';
echo '<div class="menu"><a href="forum/">Форум</a> (' . wfrm() . ')</div>';
echo '<div class="menu"><a href="chat/">Чат</a> (' . wch() . ')</div>';
echo '<div class="bmenu">Полезное</div>';
echo '<div class="menu"><a href="download/">Загрузки</a> (' . dload() . ')</div>';
echo '<div class="menu"><a href="library/">Библиотека</a> (' . stlib() . ')</div>';
echo '<div class="menu"><a href="gallery/">Галерея</a> (' . fgal() . ')</div>';
echo '<div class="bmenu"><a href="http://gazenwagen.com">Ф Газенвагенъ</a></div>';

?>