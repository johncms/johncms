<?php
/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */
 
defined('_IN_JOHNCMS') or die('Error: restricted access');
// Проверяем наличие комментируемого объекта
$req_obj = mysql_query("SELECT * FROM `library_texts` WHERE `id`=" . $id);
if (mysql_num_rows($req_obj)) {
  $res_obj = mysql_fetch_assoc($req_obj);
  if (!$res_obj['comments']) {
    echo functions::display_error($lng['access_forbidden']);
    require ('../incfiles/end.php');
    exit;
  }
  $author = mysql_result(mysql_query("SELECT `id` FROM `users` WHERE `name`='" . $res_obj['author'] . "' LIMIT 1") , 0);
  $owner = functions::get_user($author);
  if (!$owner) {
    echo functions::display_error($lng['user_does_not_exist']);
    require ('../incfiles/end.php');
    exit;
  }
  $article = mysql_fetch_assoc(mysql_query("SELECT `name`, `time`, `announce` FROM `library_texts` WHERE `id` = " . $id));
  $catalog = mysql_fetch_assoc(mysql_query("SELECT `id`, `name` FROM `library_cats` WHERE `id`=" . $res_obj['cat_id'] . " LIMIT 1"));
  $context_top = '<div class="phdr"><a href="?"><b>' . $lng['library'] . '</b></a> | <a href="?do=dir&amp;id=' . $catalog['id'] . '">' . $catalog['name'] . '</a> | ' . $res_obj['name'] . '</div>';
  $context_top .= '<div class="bmenu"><a href="?do=text&amp;id=' . $id . '">' . $article['name'] . '</a></div>';
  $context_top .= '<div class="menu">'; 
  $context_top .= '<div>' . $lng['author'] . ' <a href="../users/profile.php?user=' . $owner['id'] . '"><b>' . $owner['name'] . '</b></a></div>';
  $context_top .= '<div>' . $lng_lib['article_added'] . ' ' . date('d-m-Y H:i:s', $article['time']) . '</div>';
  $context_top .= '<div>' . functions::smileys(functions::checkout('[spoiler=' . $lng_lib['announce'] . ']' . $article['announce'] . '[/spoiler]', 1, 1)) . '</div>';
  $context_top .= '</div>';
  $arg = [
    'comments_table' => 'cms_library_comments', // Таблица с комментариями
    'object_table' => 'library_texts', // Таблица комментируемых объектов
    'script' => '?act=comments', // Имя скрипта (с параметрами вызова)
    'sub_id_name' => 'id', // Имя идентификатора комментируемого объекта
    'sub_id' => $id, // Идентификатор комментируемого объекта
    'owner' => $owner['id'], // Владелец объекта
    'owner_delete' => true, // Возможность владельцу удалять комментарий
    'owner_reply' => false, // Возможность владельцу отвечать на комментарий
    'owner_edit' => false, // Возможность владельцу редактировать комментарий
    'title' => $lng['comments'], // Название раздела
    'context_top' => $context_top, // Выводится вверху списка
    // 'context_bottom' => ''                                                 // Выводится внизу списка
  ];
  $comm = new comments($arg);
  if ($comm->added) {
      mysql_query("UPDATE `library_texts` SET `count_comments`=" . ($res_obj['count_comments'] > 0 ? ++$res_obj['count_comments'] : 1) . " WHERE `id`=" . $id);
  }
}
else {
  echo functions::display_error($lng['error_wrong_data']);
}