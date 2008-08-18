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
{
    echo '<a href=\'' . $home . '\'>На главную</a><br/>';
}
// Выводим меню быстрого перехода
if (empty($_SESSION['uid']) || $datauser['pereh'] != 1)
{
    echo "<form action='" . $home . "/go.php' method='post'><select name='adres' style='font-size:10px'><option selected='selected'>Быстрый переход </option>";
    if (!empty($_SESSION['uid']))
    {
        echo "<option value='privat'>Приват</option><option value='set'>Настройки</option><option value='prof'>Анкета</option><option value='chat'>Чат</option>";
    }
    echo "<option value='guest'>Гостевая</option><option value='forum'>Форум:</option>";
    $fr = @mysql_query("select `id`, `text` from `forum` where type='f';");
    while ($fr1 = mysql_fetch_array($fr))
    {
        echo "<option value='frm." . $fr1[id] . "'>&nbsp;- $fr1[text]&quot;</option>";
    }
    echo "<option value='news'>Новости</option><option value='gallery'>Галерея</option><option value='down'>Загрузки</option><option value='lib'>Библиотека</option><option value='gazen'>Ф Газенвагенъ</option></select><input style='font-size:9px' type='submit' value='Go!'/></form>";
}
//echo '</div>';

// Выводим счетчик посетителей Online
$ontime = $realtime - 300;
$qon = @mysql_query("SELECT * FROM `users` WHERE `lastdate`>='" . $ontime . "';");
$qon2 = mysql_num_rows($qon);
$massall = array();
$all = @mysql_query("SELECT * FROM `count` WHERE `time`>='" . $ontime . "' GROUP BY `ip`, `browser`;");
$all2 = mysql_num_rows($all);
echo '</div><div class="footer">' . ($user_id ? '<a href="' . $home . '/str/online.php">Онлайн: ' . $qon2 . ' / ' . $all2 . '</a>' : 'Онлайн: ' . $qon2 . ' / ' . $all2) . '</div>';

echo '<div>';

// Выводим параметры Gzip сжатия
if ($set['gzip'])
{
    $Contents = ob_get_contents();
    $gzib_file = strlen($Contents);
    $gzib_file_out = strlen(gzcompress($Contents, 9));
    $gzib_pro = round(100 - (100 / ($gzib_file / $gzib_file_out)), 1);
    echo '<center>Cжатие вкл. (' . $gzib_pro . '%)</center>';
} else
{
    echo '<center>Cжатие выкл.</center>';
}

// Выводим счетчик переходов и времени, проведенного на сайте
if (!empty($_SESSION['uid']))
{
    $prh = @mysql_query("select * from `count` where `time`>='" . $datauser['sestime'] . "' and `name`='" . $login . "';");
    $prh1 = mysql_num_rows($prh);
    echo '<center>В онлайне: ' . gmdate('H:i:s', ($realtime - $datauser['sestime'])) . '<br />Переходов: ' . $prh1 . '</center>';
}
echo "</div>";

////////////////////////////////////////////////////////////
// Выводим счетчики и копирайты внизу страницы            //
////////////////////////////////////////////////////////////
echo '<div class="end"><b>' . $copyright . '</b><br/>';

// Счетчики
if ($headmod == "mainpage")
{
    // Тут, вставляем коды счетчиков, которые будут выводиться
    // ТОЛЬКО на главной странице сайта
    echo '';
} else
{
    // Тут, вставляем коды счетчиков, которые будут выводиться
    // на ВСЕХ страницах сайта, кроме Главной
    echo '';
}

echo '</div>';

// Данный копирайт нельзя убирать в течение 60 дней с момента установки скриптов
echo '<div class="end"><a href="http://johncms.com">JohnCMS 1.5.0</a></div>';
echo '</body></html>';

?>