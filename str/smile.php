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

define('_IN_PUSTO', 1);
session_name('SESID');
session_start();
$headmod = 'smile';
$textl = 'Смайлы';
require ("../incfiles/db.php");
require ("../incfiles/func.php");
require ("../incfiles/data.php");
require ("../incfiles/head.php");
require ("../incfiles/inc.php");


if ($_GET['act'] == "cat")
{
    $d = $_GET['d'];
    $d = htmlspecialchars(stripslashes($d));
    if (stristr($d, "../"))
    {
        echo "Ошибка!<br/><a href=\"smile.php?\">В категории</a><br/>";
        require ("../incfiles/end.php");
        exit;
    }
    $b = $d;
    require ("../incfiles/smcat.php");
    echo "<div>Категория: $b</div>";

    $dir = opendir("../sm/cat/$d");
    while ($file = readdir($dir))
    {
        if (($file != ".") && ($file != "..") && ($file != ".htaccess") && ($file != "index.php") && ($file != "Thumbs.db"))
        {
            $a[] = $file;
        }
    }
    closedir($dir);
    sort($a);

    $total = count($a);
    if ($total == 0)
    {
        echo "Ошибка!<br/><a href=\"smile.php?\">В категории</a><br/>";
        require ("../incfiles/end.php");
        exit;
    }

    if (empty($_GET['start']))
        $start = 0;
    else
        $start = $_GET['start'];
    if ($total < $start + 10)
    {
        $end = $total;
    } else
    {
        $end = $start + 10;
    }
    for ($i = $start; $i < $end; $i++)
    {

        $smkod = str_replace(".gif", "", $a[$i]);
        $smkod1 = trans($smkod);
        echo '<img src="../sm/cat/' . $d . '/' . $a[$i] . '" alt=""/>';
        echo '- :' . $smkod . ':  :' . $smkod1 . ':<br/>';
    }


    $a = count($a);
    $ba = floor($a / 10);
    $ba2 = $ba * 10;

    echo '<br/>Страницы:';
    $asd = $start - (10 * 4);
    $asd2 = $start + (10 * 5);

    if ($asd < $a && $asd > 0)
    {
        echo ' <a href="smile.php?act=cat&amp;d=' . $d . '&amp;start=0&amp;' . SID . '">1</a> ... ';
    }

    for ($i = $asd; $i < $asd2; )
    {
        if ($i < $a && $i >= 0)
        {
            $ii = floor(1 + $i / 10);

            if ($start == $i)
            {
                echo ' <b>' . $ii . '</b>';
            } else
            {
                echo ' <a href="smile.php?act=cat&amp;d=' . $d . '&amp;start=' . $i . '&amp;' . SID . '">' . $ii . '</a>';
            }
        }


        $i = $i + 10;
    }
    if ($asd2 < $a)
    {
        echo ' ... <a href="smile.php?act=cat&amp;d=' . $d . '&amp;start=' . $ba2 . '&amp;' . SID . '">' . $ba . '</a>';
    }


    echo '<br/><br/>Смайлов в категории: ' . $total . '<br/>';
    echo "<a href=\"smile.php?\">В категории</a><br/>";

}
#####################
if ($_GET['act'] == "adm")
{
    if ($dostmod != 1)
    {
        echo "Ошибка!<br/><a href=\"?\">В категории</a><br/>";
        require ("../incfiles/end.php");
        exit;
    }
    echo "<div>Категория: для администрации</div>";

    $dir = opendir("../sm/adm"); // открываем текущую директорию
    while ($file = readdir($dir))
    {
        if (($file != ".") && ($file != "..") && ($file != ".htaccess") && ($file != "index.php") && ($file != "Thumbs.db")) // эти файлы игнорируем

        {
            $a[] = $file;
        }
    } // записываем все что есть в массив
    closedir($dir); //Закрываем
    sort($a); //сортируем

    $total = count($a); #считаем
    if (empty($_GET['start']))
        $start = 0; # для вывода
    else
        $start = $_GET['start'];
    if ($total < $start + 10)
    {
        $end = $total;
    } else
    {
        $end = $start + 10;
    }
    for ($i = $start; $i < $end; $i++)
    { #цикл

        $smkod = str_replace(".gif", "", $a[$i]);
        $smkod1 = trans($smkod);
        echo '<img src="../sm/adm/' . $a[$i] . '" alt=""/>';
        echo '- :' . $smkod . ':  :' . $smkod1 . ':<br/>';
    }


    $a = count($a);
    $ba = floor($a / 10);
    $ba2 = $ba * 10;

    echo '<br/>Страницы:';
    $asd = $start - (10 * 4);
    $asd2 = $start + (10 * 5);

    if ($asd < $a && $asd > 0)
    {
        echo ' <a href="smile.php?act=adm&amp;start=0&amp;' . SID . '">1</a> ... ';
    }

    for ($i = $asd; $i < $asd2; )
    {
        if ($i < $a && $i >= 0)
        {
            $ii = floor(1 + $i / 10);

            if ($start == $i)
            {
                echo ' <b>' . $ii . '</b>';
            } else
            {
                echo ' <a href="smile.php?act=adm&amp;start=' . $i . '&amp;' . SID . '">' . $ii . '</a>';
            }
        }


        $i = $i + 10;
    }
    if ($asd2 < $a)
    {
        echo ' ... <a href="smile.php?act=adm&amp;start=' . $ba2 . '&amp;' . SID . '">' . $ba . '</a>';
    }


    echo '<br/><br/>Смайлов в категории: ' . $total . '<br/>';
    echo "<a href=\"smile.php?\">В категории</a><br/>";

}

#######################


if ($_GET['act'] == "prost")
{

    echo "<div>Категория: простые</div>";

    $dir = opendir("../sm/prost"); // открываем текущую директорию
    while ($file = readdir($dir))
    {
        if (($file != ".") && ($file != "..") && ($file != ".htaccess") && ($file != "index.php") && ($file != "Thumbs.db")) // эти файлы игнорируем

        {
            $a[] = $file;
        }
    } // записываем все что есть в массив
    closedir($dir); //Закрываем
    sort($a); //сортируем

    $total = count($a); #считаем
    if (empty($_GET['start']))
        $start = 0; # для вывода
    else
        $start = $_GET['start'];
    if ($total < $start + 10)
    {
        $end = $total;
    } else
    {
        $end = $start + 10;
    }
    for ($i = $start; $i < $end; $i++)
    { #цикл

        $smkod = str_replace(".gif", "", $a[$i]);

        echo '<img src="../sm/prost/' . $a[$i] . '" alt=""/>';
        echo '- :' . $smkod . '<br/>';
    }


    $a = count($a);
    $ba = floor($a / 10);
    $ba2 = $ba * 10;

    echo '<br/>Страницы:';
    $asd = $start - (10 * 4);
    $asd2 = $start + (10 * 5);

    if ($asd < $a && $asd > 0)
    {
        echo ' <a href="smile.php?act=prost&amp;start=0&amp;' . SID . '">1</a> ... ';
    }

    for ($i = $asd; $i < $asd2; )
    {
        if ($i < $a && $i >= 0)
        {
            $ii = floor(1 + $i / 10);

            if ($start == $i)
            {
                echo ' <b>' . $ii . '</b>';
            } else
            {
                echo ' <a href="smile.php?act=prost&amp;start=' . $i . '&amp;' . SID . '">' . $ii . '</a>';
            }
        }


        $i = $i + 10;
    }
    if ($asd2 < $a)
    {
        echo ' ... <a href="smile.php?act=prost&amp;start=' . $ba2 . '&amp;' . SID . '">' . $ba . '</a>';
    }


    echo '<br/><br/>Смайлов в категории: ' . $total . '<br/>';
    echo "<a href=\"smile.php?\">В категории</a><br/>";

}

if ($_GET['act'] == "")
{
    if (empty($_SESSION['refsm']))
    {
        $_SESSION['refsm'] = $ref;
    }
    if ($dostmod == 1)
    {
        echo ' <a href="smile.php?act=adm&amp;">Смайлы для администрации</a><br/>';
    }
    echo ' <a href="smile.php?act=prost&amp;">Простые смайлы</a><br/>';
    $dir = opendir("../sm/cat");
    while ($file = readdir($dir))
    {
        if (($file != ".") && ($file != "..") && ($file != ".htaccess") && ($file != "index.php"))
        {
            $a[] = $file;
        }
    }
    closedir($dir);

    $total = count($a);

    sort($a); //сортируем
    $b = $a;
    require ("../incfiles/smcat.php");

    for ($i = 0; $i < $total; $i++)
    {
        echo ' <a href="smile.php?act=cat&amp;d=' . $a[$i] . '&amp;">' . $b[$i] . '</a><br/>';
    }
    echo "<br/>";
    $back = $_SESSION['refsm'];
    if (stristr($back, "pradd.php"))
    {
        $backtext = "В приват";
    }
    if (stristr($back, "cont.php"))
    {
        $backtext = "В контакты";
    }
    //if (!empty($backtext)){
    echo "<a href=\"$back\">Назад</a><br/>";
}
//}
require ('../incfiles/end.php');
?>