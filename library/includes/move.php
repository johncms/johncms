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
if ($adm) {
  $type = isset($_GET['moveset']) && in_array($_GET['moveset'], ['up', 'down']) ? $_GET['moveset'] : redir404();
  $posid = isset($_GET['posid']) && $_GET['posid'] > 0 ? intval($_GET['posid']) : redir404();
  list($num1, $pos1) = explode('|', $arrsort[$posid]);
  list($num2, $pos2) = explode('|', $arrsort[($type == 'up' ? $posid - 1 : $posid + 1)]);
  mysql_query('UPDATE `library_cats` SET `pos`=' . $pos2 . ' WHERE `id`=' . $num1);
  mysql_query('UPDATE `library_cats` SET `pos`=' . $pos1 . ' WHERE `id`=' . $num2);
  header('Location: ' . $_SERVER['HTTP_REFERER']);
  exit;
}