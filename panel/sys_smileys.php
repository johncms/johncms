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

defined('_IN_JOHNADM') or die('Error: restricted access');

if ($rights < 7)
    die('Error: restricted access');

echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Смайлы</div>';
if ($total = smileys(0, 2)) {
    echo '<div class="gmenu"><p>Кэш смайлов успешно обновлен</p></div>';
}
else {
    echo '<div class="rmenu"><p>Ошибка лоступа к Кэшу смайлов</p></div>';
    $total = 0;
}
echo '<div class="phdr">Всего смайлов: ' . $total . '</div>';
echo '<p><a href="index.php">Админ панель</a></p>';

?>