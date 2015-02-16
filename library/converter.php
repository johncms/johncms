<?php
/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */
 
define('_IN_JOHNCMS', 1);
$headmod = 'library';
require_once ('../incfiles/core.php');
$textl = $lng['library'] . ' - Конвертер';
require_once ('../incfiles/head.php');

if ($rights != 9) {
  header('Location: ' . $_SERVER['HTTP_REFERER']);
  exit;
}

if ($rights == 9 && !file_exists('converter_not_deleted')) {
  $sql = mysql_query("SELECT `id`, `refid`, `text`, `ip` FROM `lib` WHERE `type`='cat'");
  while ($row = mysql_fetch_assoc($sql)) {
    mysql_query("INSERT INTO `library_cats` SET `id`=" . $row['id'] . ", `parent`=" . $row['refid'] . ", `dir`=" . $row['ip'] . ", `pos`=" . $row['id'] . ", `name`='" . $row['text'] . "'");
  }

  $sql = mysql_query("SELECT `id`, `refid`, `text`, `avtor`, `name`, `moder`, `count`, `time` FROM `lib` WHERE `type`='bk'");
  while ($row = mysql_fetch_assoc($sql)) {
    mysql_query("INSERT INTO `library_texts` SET `id`=" . $row['id'] . ", `cat_id`=" . $row['refid'] . ", `author`='" . $row['avtor'] . "', `text`='" . $row['text'] . "', `name`='" . $row['name'] . "', `premod`=" . $row['moder'] . ", `count_views`=" . $row['count'] . ", `time`='" . $row['time'] . "'");
  }

  $array = array();
  $sql = mysql_query("SELECT `id`,`refid`, `avtor`, `text`, `ip`, `soft` FROM `lib` WHERE `type`='komm'");
  while ($row = mysql_fetch_assoc($sql)) {
    $attributes = array(
      'author_name' => $row['avtor'],
      'author_ip' => $row['ip'],
      'author_ip_via_proxy' => $row['ip'],
      'author_browser' => $row['soft']
    );
    $array[$row['refid']][] = $row['id'];
    mysql_query("INSERT INTO `cms_library_comments` SET `sub_id`=" . $row['refid'] . ", `time`='" . time() . "', `user_id`=" . (mysql_result(mysql_query("SELECT `id` FROM `users` WHERE `name`='" . $row['avtor'] . "' LIMIT 1") , 0)) . ", `text`='" . $row['text'] . "', `attributes`='" . mysql_real_escape_string(serialize($attributes)) . "'");
  }

  foreach($array as $aid => $cnt) {
    mysql_query("UPDATE `library_texts` SET `count_comments`=" . count($cnt) . ", `comments`=1 WHERE `id`=" . $aid);
  }

  echo '<div>Конвертация успешно произведена</div>';
  file_put_contents('converter_not_deleted', date('d-m-Y H:i:s'));
}
else {
  echo '<div>Сначала надо удалить файл converter с корня библиотеки</div>';
}

require_once ('../incfiles/end.php');