<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

$textl = $lng['profile'] . ' | ' . $lng['guestbook'];
$headmod = 'my_guest';
if($user_id && $user['id'] == $user_id)
    $datauser['comm_old'] = $datauser['comm_count'];
require('../incfiles/head.php');

$context_top = '<div class="phdr"><a href="profile.php?user=' . $user['id'] . '"><b>' . $lng['profile'] . '</b></a> | ' . $lng['guestbook'] . '</div>' .
    '<div class="user"><p>' . functions::display_user($user, array ('iphide' => 1,)) . '</p></div>';

/*
-----------------------------------------------------------------
Параметры Гостевой
-----------------------------------------------------------------
*/
$arg = array (
    'comments_table' => 'cms_users_guestbook', // Таблица Гостевой
    'object_table' => 'users',                 // Таблица комментируемых объектов
    'script' => 'profile.php?act=guestbook',   // Имя скрипта (с параметрами вызова)
    'sub_id_name' => 'user',                   // Имя идентификатора комментируемого объекта
    'sub_id' => $user['id'],                   // Идентификатор комментируемого объекта
    'owner' => $user['id'],                    // Владелец объекта
    'owner_delete' => true,                    // Возможность владельцу удалять комментарий
    'owner_reply' => true,                     // Возможность владельцу отвечать на комментарий
    'title' => $lng['comments'],               // Название раздела
    'context_top' => $context_top              // Выводится вверху списка
);

/*
-----------------------------------------------------------------
Показываем комментарии
-----------------------------------------------------------------
*/
$comm = new comments($arg);

/*
-----------------------------------------------------------------
Обновляем счетчик непрочитанного
-----------------------------------------------------------------
*/
if(!$mod && $user['id'] == $user_id && $user['comm_count'] != $user['comm_old']){
    $db->exec("UPDATE `users` SET `comm_old` = '" . $user['comm_count'] . "' WHERE `id` = '$user_id' LIMIT 1");
}
