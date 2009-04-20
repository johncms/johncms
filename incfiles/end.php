<?php

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

defined('_IN_JOHNCMS') or die('Error:restricted access');

echo '</div><div class="fmenu">';
if ($headmod != "mainpage" || isset($_GET['do']) || isset($_GET['mod']))
    echo '<a href=\'' . $home . '\'>На главную</a><br/>';

// Меню быстрого перехода
if (empty($_SESSION['uid']) || $datauser['pereh'] == 1)
{
    echo "<form action='" . $home . "/go.php' method='post'><select name='adres' style='font-size:10px'><option selected='selected'>Быстрый переход </option>";
    if ($user_id)
    {
        echo "<option value='privat'>Приват</option><option value='set'>Настройки</option><option value='prof'>Анкета</option><option value='chat'>Чат</option>";
    }
    echo "<option value='guest'>Гостевая</option><option value='forum'>Форум</option>";
    echo "<option value='news'>Новости</option><option value='gallery'>Галерея</option><option value='down'>Загрузки</option><option value='lib'>Библиотека</option><option value='gazen'>Ф Газенвагенъ</option></select><input style='font-size:9px' type='submit' value='Go!'/></form>";
}
// Счетчик посетителей онлайн
echo '</div><div class="footer">' . usersonline() . '</div>';

////////////////////////////////////////////////////////////
// Выводим информацию внизу страницы                      //
////////////////////////////////////////////////////////////
echo '<div align="center">';
zipcount(); // Индикатор сжатия
timeonline(); // Время, проведенное в онлайне
movements(); // Счетчик перемещений по сайту
echo '<div><b>' . $copyright . '</b></div>';
counters(); // Счетчики каталогов

////////////////////////////////////////////////////////////
// ВНИМАНИЕ!!!                                            //
// Данный копирайт нельзя убирать в течение 60 дней       //
// с момента установки скриптов                           //
////////////////////////////////////////////////////////////
echo '<div><small><a href="http://johncms.com">JohnCMS</a></small></div>';
echo '</div></body></html>';

?>