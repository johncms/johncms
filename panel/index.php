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

@ ini_set("max_execution_time", "600");
define('_IN_JOHNCMS', 1);
define('_IN_JOHNADM', 1);

$textl = 'Админ панель';
require_once ('../incfiles/core.php');

if ($rights < 1) {
    header('Location: http://gazenwagen.com/?err');
    exit;
}

require_once ('../incfiles/head.php');
$array = array('usr_reg', 'usr_adm', 'usr_list', 'usr_del', 'usr_ban', 'usr_search_nick', 'usr_search_ip', 'mod_ads', 'mod_counters', 'mod_news', 'mod_forum', 'mod_chat', 'sys_set', 'sys_smileys', 'sys_access', 'sys_antispy', 'sys_ipban', 'mod_karma', 'sys_flood');
if (in_array($act, $array) && file_exists($act . '.php')) {
    require_once ($act . '.php');
}
else {
    ////////////////////////////////////////////////////////////
    // Главное меню админки                                   //
    ////////////////////////////////////////////////////////////
    echo '<div class="phdr"><b>Админ панель</b></div>';
    echo '<div class="gmenu"><p><h3><img src="../images/users.png" width="16" height="16" class="left" />&nbsp;Пользователи</h3><ul>';
    $regtotal = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `preg`='0'"), 0);
    if ($regtotal)
        echo '<li><span class="red"><b><a href="index.php?act=usr_reg">На регистрации</a>&nbsp;(' . $regtotal . ')</b></span></li>';
    echo '<li><a href="index.php?act=usr_adm">Администрация</a>&nbsp;(' . mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `rights` >= '1'"), 0) . ')</li>';
    //TODO: Написать показ числа новых
    echo '<li><a href="index.php?act=usr_list">Пользователи</a>&nbsp;(' . mysql_result(mysql_query("SELECT COUNT(*) FROM `users`"), 0) . ')</li>';
    $bantotal = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `ban_time` > '$realtime'"), 0);
    echo '<li><a href="index.php?act=usr_ban">Бан-панель</a>&nbsp;(' . $bantotal . ')</li>';
    echo '</ul></p><p><h3><img src="../images/search.png" width="16" height="16" class="left" />&nbsp;Поиск</h3><ul>';
    echo '<li><a href="index.php?act=usr_search_nick">Поиск по Нику</a></li>';
    echo '<li><a href="index.php?act=usr_search_ip">Поиск по IP</a></li>';
    echo '</ul></p></div>';
    echo '<div class="menu">';
    // Блок модулей
    if ($rights >= 7) {
        echo '<p><h3><img src="../images/modules.png" width="16" height="16" class="left" />&nbsp;Модули</h3><ul>';
        echo '<li><a href="index.php?act=mod_ads">Реклама</a></li>';
        if ($rights == 9)
            echo '<li><a href="index.php?act=mod_counters">Счетчики</a></li>';
        echo '<li><a href="index.php?act=mod_news">Новости</a></li>';
        echo '<li><a href="index.php?act=mod_forum">Форум</a></li>';
        echo '<li><a href="index.php?act=mod_chat">Чат</a></li>';
        echo '<li><a href="index.php?act=mod_karma">Карма</a></li>';
        echo '</ul></p>';
    }
    echo '</div>';
    // Блок системных настроек
    if ($rights >= 7) {
        echo '<div class="bmenu"><p><h3><img src="../images/settings.png" width="16" height="16" class="left" />&nbsp;Система</h3><ul>';
        if ($rights == 9)
            echo '<li><a href="index.php?act=sys_set">Настройки сайта</a></li>';
        echo '<li><a href="index.php?act=sys_smileys">Обновить смайлы</a></li>';
        //echo '<li><a href="">Очистка</a></li>';
        echo '</ul></p>';
        echo '<p><h3><img src="../images/admin.png" width="16" height="16" class="left" />&nbsp;Безопасность</h3><ul>';
        echo '<li><a href="index.php?act=sys_flood">Антифлуд</a></li>';
        echo '<li><a href="index.php?act=sys_access">Права доступа</a></li>';
        echo '<li><a href="index.php?act=sys_antispy">Сканер антишпион</a></li>';
        if ($rights == 9)
            echo '<li><a href="index.php?act=sys_ipban">Бан по IP</a></li>';
        echo '</ul></p></div>';
    }
}

require_once ('../incfiles/end.php');

?>