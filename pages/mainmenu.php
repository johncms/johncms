<?
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

////////////////////////////////////////////////////////////
// Выводим 2 последние новости на Главную                 //
////////////////////////////////////////////////////////////

$headnews = false; // По умолчанию false. Если нужно выводить новости, поставьте true

if ($headmod == "mainpage" && !isset($_GET['do']) && $headnews)
{
    echo '<div>';
    $nw = mysql_query("select * from `news` order by time desc LIMIT 2;");
    if (!empty($_GET['kv']))
    {
        $count = intval(check($_GET['kv']));
    } else
    {
        $count = mysql_num_rows($nw);
    }
    while ($nw1 = mysql_fetch_array($nw))
    {
        $nw1[text] = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class=\'d\'>\1<br/></div>', $nw1[text]);
        $nw1[text] = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $nw1[text]);
        $nw1[text] = eregi_replace("\\[l\\]((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='\\1\\3'>\\7</a>", $nw1[text]);
        if (stristr($nw1[text], "<a href="))
        {
            $nw1[text] = eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)</a>",
                "<a href='\\1\\3'>\\3</a>", $nw1[text]);
        } else
        {
            $nw1[text] = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)", "<a href='\\1\\3'>\\3</a>", $nw1[text]);
        }
        if ($offsm != 1 && $offgr != 1)
        {
            $tekst = smiles($nw1[text]);
            $tekst = smilescat($tekst);
            if ($nw1[from] == nickadmina || $nw1[from] == nickadmina2 || $nw11[rights] >= 1)
            {
                $tekst = smilesadm($tekst);
            }
        } else
        {
            $tekst = $nw1[text];
        }
        $vr = $nw1[time] + $sdvig * 3600;
        $vr1 = date("d.m.y / H:i", $vr);
        echo "<b>$nw1[name]</b><br/>$tekst<br/>";
        if ($nw1[kom] != 0 && $nw1[kom] != "")
        {
            $mes = mysql_query("select * from `forum` where type='m' and refid= '" . $nw1[kom] . "';");
            $komm = mysql_num_rows($mes) - 1;
            echo "<a href='../forum/?id=" . $nw1[kom] . "'>Комменты ($komm)</a><br/>";
        }
        ++$i;
    }
    echo '</div>';
}

////////////////////////////////////////////////////////////
// Главное меню сайта                                     //
////////////////////////////////////////////////////////////
$do = isset($_GET['do']) ? $_GET['do'] : '';
switch ($do)
{
    case 'cab': // Подраздел личного кабинета
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">1</span>&nbsp;<a href="' . $home . '/str/privat.php" accesskey="1">Личная почта</a></div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">2</span>&nbsp;<a href="' . $home . '/str/anketa.php" accesskey="2">Ваша анкета</a></div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">3</span>&nbsp;<a href="' . $home . '/str/usset.php" accesskey="3">Настройки</a></div>';
        if ($dostmod == 1)
        {
            echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">0</span>&nbsp;<a href="' . $home . '/' . $admp . '/main.php" accesskey="0">Админка</a></div>';
        }
        break;

    case 'info': // Подраздел информации
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">1</span>&nbsp;<a href="str/users.php" accesskey="1">Список юзеров</a> (' . kuser() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">2</span>&nbsp;<a href="str/brd.php" accesskey="2">Именинники</a> (' . brth() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">3</span>&nbsp;<a href="str/moders.php" accesskey="3">Администрация</a></div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">4</span>&nbsp;<a href="str/smile.php?" accesskey="4">Смайлы</a></div>';
        break;

    default: // Главное меню
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">1</span>&nbsp;<a href="str/news.php" accesskey="1">Все новости</a> (' . dnews() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">2</span>&nbsp;<a href="index.php?do=info" accesskey="2">Информация</a></div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">3</span>&nbsp;<a href="str/guest.php" accesskey="3">Гостевая</a> (' . gbook() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">4</span>&nbsp;<a href="forum/?" accesskey="4">Форум</a> ('. wfrm() . ')</div>';        
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">5</span>&nbsp;<a href="chat/?" accesskey="5">Чат</a> (' . wch() . ')</div>';             
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">6</span>&nbsp;<a href="gallery/?" accesskey="6">Галлерея</a> (' . fgal() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">7</span>&nbsp;<a href="str/lib.php" accesskey="7">Библиотека</a> (' . stlib() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">8</span>&nbsp;<a href="download/download.php" accesskey="8">Загрузки</a> (' . dload() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">9</span>&nbsp;<a href="read.php?" accesskey="9">FAQ (ЧаВо)</a></div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" /> &nbsp;<span class="ackey">0</span>&nbsp;<a href="http://gazenwagen.com" accesskey="0">Ф Газенвагенъ</a></div>';
}

?>


