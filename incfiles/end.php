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

defined('_IN_JOHNCMS') or die('Error: restricted access');

// Рекламный блок MOBILEADS.RU
if (isset ($_SESSION['mad_links']) && $_SESSION['mad_time'] > ($realtime - 60 * 15))
    echo '<div class="gmenu">' . $_SESSION['mad_links'] . '</div>';

// Рекламный блок сайта
if (!empty ($cms_ads[2]))
    echo '<div class="gmenu">' . $cms_ads[2] . '</div>';

echo '</div><div class="fmenu">';
if ($headmod != "mainpage" || ($headmod == 'mainpage' && $act))
    echo '<a href=\'' . $home . '\'>На главную</a><br/>';

// Меню быстрого перехода
if ($set_user['quick_go']) {
    echo '<form action="' . $home . '/go.php" method="post">';
    echo
    '<div><select name="adres" style="font-size:x-small">
	<option selected="selected">Быстрый переход</option>
	<option value="guest">Гостевая</option>
	<option value="forum">Форум</option>
    <option value="news">Новости</option>
    <option value="gallery">Галерея</option>
    <option value="down">Загрузки</option>
    <option value="lib">Библиотека</option>
    <option value="chat">Чат</option>
    <option value="gazen">Газенвагенъ</option>
    </select><input type="submit" value="Go!" style="font-size:x-small"/>';
    echo '</div></form>';
}
// Счетчик посетителей онлайн
echo '</div><div class="footer">' . usersonline() . '</div>';

////////////////////////////////////////////////////////////
// Выводим информацию внизу страницы                      //
////////////////////////////////////////////////////////////
echo '<div style="text-align:center">';
echo '<p><b>' . $copyright . '</b></p>';
if (!$user_id || ($user_id && $set_user['gzip']))
    zipcount();// Индикатор сжатия
if (!$user_id || ($user_id && $set_user['online']))
    timeonline();// Время, проведенное в онлайне
if (!$user_id || ($user_id && $set_user['movings']))
    echo 'Переходов: ' . $movings;// Счетчик перемещений по сайту
counters();// Счетчики каталогов

// Рекламный блок сайта
if (!empty ($cms_ads[3]))
    echo $cms_ads[3];

////////////////////////////////////////////////////////////
// ВНИМАНИЕ!!!                                            //
// Данный копирайт нельзя убирать в течение 60 дней       //
// с момента установки скриптов                           //
////////////////////////////////////////////////////////////
echo '<div><small><a href="http://johncms.com">JohnCMS</a></small></div>';
echo '</div></body></html>';

ob_end_flush();

// Блок Mobileads.ru
if (!isset ($_SESSION['mad_time']) || $_SESSION['mad_time'] < ($realtime - 60 * 3)) {
    $out = '';
    $mad_socketTimeout = 2;    // таймаут соединения с сервером mobileads.ru
    ini_set("default_socket_timeout", $mad_socketTimeout);
    #######################
    // В строке ниже, укажите ID своего сайта
    $mad_siteId = 0;
    $mad_pageEncoding = "UTF-8";    // устанавливаем кодировку страницы
    $mad_ua = urlencode(@ $_SERVER['HTTP_USER_AGENT']);
    $mad_ip = urlencode(@ $_SERVER['REMOTE_ADDR']);
    $mad_xip = urlencode(@ $_SERVER['HTTP_X_FORWARDED_FOR']);
    $mad_ref = urlencode(@ $_SERVER['SERVER_NAME'] . @ $_SERVER['REQUEST_URI']);
    $mad_lines = "";
    $mad_fp = @ fsockopen("mobileads.ru", 80, $mad_errno, $mad_errstr, $mad_socketTimeout);
    if ($mad_fp) {
        // переменная $mad_lines будет содержать массив, непарные элементы которого будут ссылками, парные - названием
        $mad_lines = @ file("http://mobileads.ru/links?id=$mad_siteId&ip=$mad_ip&xip=$mad_xip&ua=$mad_ua&ref=$mad_ref");
    }
    @ fclose($mad_fp);    // вывод ссылок
    for ($malCount = 0; $malCount < count($mad_lines); $malCount += 2) {
        $linkURL = trim($mad_lines[$malCount]);
        $linkName = iconv("Windows-1251", $mad_pageEncoding, $mad_lines[$malCount + 1]);
        $out .= '<a href="' . $linkURL . '">' . $linkName . '</a><br />';
    }
    $_SESSION['mad_links'] = $out;
    $_SESSION['mad_time'] = $realtime;
}

?>