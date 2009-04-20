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

$do = isset($_GET['do']) ? $_GET['do'] : '';
switch ($do)
{
    case 'info':
        ////////////////////////////////////////////////////////////
        // Подраздел информации                                   //
        ////////////////////////////////////////////////////////////
        echo '<div class="phdr">Информация</div>';
        echo '<div class="menu"><a href="str/users.php">Список юзеров</a> (' . kuser() . ')</div>';
        echo '<div class="menu"><a href="str/brd.php">Именинники</a> (' . brth() . ')</div>';
        echo '<div class="menu"><a href="str/moders.php">Администрация</a></div>';
        echo '<div class="menu"><a href="str/smile.php?">Смайлы</a></div>';
        $_SESSION['refsm'] = '../index.php?do=info';
        break;

    default:
        ////////////////////////////////////////////////////////////
        // Главное меню сайта                                     //
        ////////////////////////////////////////////////////////////
        require_once ('incfiles/class_mainpage.php');
        $mp = new mainpage();
        // Блок новостей
        echo $mp->news;
        echo '<div class="bmenu">Информация</div>';
		echo '<div class="menu"><a href="str/news.php">Архив новостей</a> (' . $mp->newscount . ')</div>';
        echo '<div class="menu"><a href="read.php?">FAQ (ЧаВо)</a></div>';
        echo '<div class="menu"><a href="index.php?do=info">Статистика</a></div>';
        echo '<div class="bmenu">Общение</div>';
        echo '<div class="menu"><a href="str/guest.php">Гостевая</a> (' . gbook() . ')</div>';
        echo '<div class="menu"><a href="forum/">Форум</a> (' . wfrm() . ')</div>';
        echo '<div class="menu"><a href="chat/">Чат</a> (' . wch() . ')</div>';
        echo '<div class="bmenu">Полезное</div>';
        echo '<div class="menu"><a href="download/">Загрузки</a> (' . dload() . ')</div>';
        echo '<div class="menu"><a href="library/">Библиотека</a> (' . stlib() . ')</div>';
        echo '<div class="menu"><a href="gallery/">Галерея</a> (' . fgal() . ')</div>';
        echo '<div class="bmenu"><a href="http://gazenwagen.com">Ф Газенвагенъ</a></div>';
}

?>