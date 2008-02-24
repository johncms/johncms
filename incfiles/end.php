<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.0.0 RC2                                                        //
// Дата релиза: 08.02.2008                                                    //
// Авторский сайт: http://gazenwagen.com                                      //
////////////////////////////////////////////////////////////////////////////////
// Оригинальная идея и код: Евгений Рябинин aka JOHN77                        //
// E-mail: 
// Модификация, оптимизация и дизайн: Олег Касьянов aka AlkatraZ              //
// E-mail: alkatraz@batumi.biz                                                //
// Плагиат и удаление копирайтов заруганы на ближайших родственников!!!       //
////////////////////////////////////////////////////////////////////////////////
// Внимание!                                                                  //
// Авторские версии данных скриптов публикуются ИСКЛЮЧИТЕЛЬНО на сайте        //
// http://gazenwagen.com                                                      //
// Если Вы скачали данный скрипт с другого сайта, то его работа не            //
// гарантируется и поддержка не оказывается.                                  //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_PUSTO') or die('Error:restricted access');

echo '</div><div class="fmenu">';
// Выводим меню быстрого перехода
if (empty($_SESSION['pid']) || $pereh != 1)
{
    echo "<form action='" . $home . "/go.php' method='post'><select name='adres' style='font-size:10px'><option selected='selected'>Быстрый переход </option>";
    if (!empty($_SESSION['pid']))
    {
        echo "<option value='privat'>Приват</option><option value='set'>Настройки</option><option value='prof'>Анкета</option><option value='chat'>Чат</option>";
    }
    echo "<option value='guest'>Гостевая</option><option value='forum'>Форум:</option>";
    $fr = @mysql_query("select * from `forum` where type='f';");
    while ($fr1 = mysql_fetch_array($fr))
    {
        echo "<option value='frm." . $fr1[id] . "'>&nbsp;&quot; $fr1[text]&quot;</option>";
    }
    echo "<option value='news'>Новости</option><option value='gallery'>Галерея</option><option value='down'>Загрузки</option><option value='lib'>Библиотека</option><option value='gazen'>Ф Газенвагенъ</option></select><input style='font-size:9px' type='submit' value='Go!'/><br/></form>";    
     ####7.02.08##########
}
if ($headmod != "mainpage" || isset($_GET['do']))
{
    echo '<a href=\'' . $home . '\'>На главную</a><br/>';
}
echo '</div>';

// Выводим счетчик посетителей Online
$ontime = $realtime - 300;
$qon = @mysql_query("select * from `users` where lastdate>='" . intval($ontime) . "';");
$qon2 = mysql_num_rows($qon);
$massall = array();
$all = @mysql_query("select * from `count` where time>='" . intval($ontime) . "' order by time desc;");
while ($all1 = mysql_fetch_array($all))
{
    $ipbr = "$all1[ip]--$all1[browser]";
    if (!in_array($ipbr, $massall))
    {
        $massall[] = $ipbr;
    }
}
$all2 = count($massall);
$massall = array();
if (!empty($_SESSION['pid']))
{
    echo '<div class="footer"><a href=\'' . $home . '/str/online.php\'>Онлайн: ' . $qon2 . ' / ' . $all2 . '</a></div>';
} else
{
    echo '<div class="footer">Онлайн: ' . $qon2 . ' / ' . $all2 . '</div>';
}

echo '<div>';

// Выводим параметры Gzip сжатия
if ($gzip == "1")
{
    $Contents = ob_get_contents();
    $gzib_file = strlen($Contents);
    $gzib_file_out = strlen(gzcompress($Contents, 9));
    $gzib_pro = round(100 - (100 / ($gzib_file / $gzib_file_out)), 1);
    echo '<center>Cжатие вкл. (' . $gzib_pro . '%)</center>';
}
if ($gzip == "0")
{
    echo '<center>Cжатие выкл.</center>';
}

// Выводим счетчик переходов и времени, проведенного на сайте
if (!empty($_SESSION['pid']))
{
    $prh = @mysql_query("select * from `count` where time>='" . intval($datauser[sestime]) . "' and name='" . $login . "';");
    $prh1 = mysql_num_rows($prh);
    $svr = $realtime - $datauser[sestime];
    if ($svr >= "3600")
    {
        $hvr = ceil($svr / 3600) - 1;
        if ($hvr < 10)
        {
            $hvr = "0$hvr";
        }
        $svr1 = $svr - $hvr * 3600;
        $mvr = ceil($svr1 / 60) - 1;
        if ($mvr < 10)
        {
            $mvr = "0$mvr";
        }
        $ivr = $svr1 - $mvr * 60;
        if ($ivr < 10)
        {
            $ivr = "0$ivr";
        }
        if ($ivr == "60")
        {
            $ivr = "59";
        }
        $sitevr = "$hvr:$mvr:$ivr";
    } else
    {
        if ($svr >= "60")
        {
            $mvr = ceil($svr / 60) - 1;
            if ($mvr < 10)
            {
                $mvr = "0$mvr";
            }
            $ivr = $svr - $mvr * 60;
            if ($ivr < 10)
            {
                $ivr = "0$ivr";
            }
            if ($ivr == "60")
            {
                $ivr = "59";
            }
            $sitevr = "00:$mvr:$ivr";
        } else
        {
            $ivr = $svr;
            if ($ivr < 10)
            {
                $ivr = "0$ivr";
            }
            $sitevr = "00:00:$ivr";
        }
    }
    echo '<center>В онлайне: ' . $sitevr . '<br />Переходов: ' . $prh1 . '</center>';
}
echo "</div>";

////////////////////////////////////////////////////////////
// Выводим счетчики и копирайты внизу страницы            //
////////////////////////////////////////////////////////////
echo '<div class="end"><b>&#169;' . $copyright . '</b><br/>';

// Счетчики
if ($headmod == "mainpage")
{
    echo ''; // На Главной странице
} else
{
    echo ''; // На остальных страницах
}

echo '</div>';

############

    echo '<div class="end"><a href="http://gazenwagen.com">Powered by JohnCMS ver. 1.0</a></div>';   ###7.02.08
    
 #################### 
    
echo '</body></html>';

ob_end_flush();
exit;

?>