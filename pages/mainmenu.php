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

defined('_IN_JOHNCMS') or die('Error:restricted access');

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
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="' . $home . '/str/privat.php">Личная почта</a></div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="' . $home . '/str/anketa.php">Ваша анкета</a></div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="' . $home . '/str/usset.php">Настройки</a></div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="' . $home . '/str/anketa.php?act=statistic">Статистика</a></div>';
        if ($dostmod == 1)
        {
            echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="' . $home . '/' . $admp . '/main.php">Админка</a></div>';
        }
        break;

    case 'info': // Подраздел информации
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="str/users.php">Список юзеров</a> (' . kuser() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="str/brd.php">Именинники</a> (' . brth() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="str/moders.php">Администрация</a></div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="str/smile.php?">Смайлы</a></div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="read.php?">FAQ (ЧаВо)</a></div>';
        $_SESSION['refsm'] = '../index.php?do=info';
		break;

    default: // Главное меню
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="str/news.php">Все новости</a> (' . dnews() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="index.php?do=info">Информация</a></div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="str/guest.php">Гостевая</a> (' . gbook() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="forum/">Форум</a> (' . wfrm() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="chat/">Чат</a> (' . wch() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="gallery/">Галерея</a> (' . fgal() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="library/">Библиотека</a> (' . stlib() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="download/">Загрузки</a> (' . dload() . ')</div>';
        echo '<div class="menu"><img alt="" src="images/arrow.gif" width="7" height="12" />&nbsp;<a href="http://gazenwagen.com">Ф Газенвагенъ</a></div>';
}

?>