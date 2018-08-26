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
$textl = $lng['mail'];
require_once('../incfiles/head.php');
if($id) {
	$stmt = $db->query("SELECT * FROM `cms_mail` WHERE (`user_id`='$user_id' OR `from_id`='$user_id') AND `id` = '$id' AND `file_name` != '' AND `delete`!='$user_id' LIMIT 1");
    if (!$stmt->rowCount()) {
		//Выводим ошибку
		echo functions::display_error($lng_mail['file_does_not_exist']);
        require_once("../incfiles/end.php");
        exit;
    }
	$res = $stmt->fetch();
	if(file_exists('../files/mail/' . $res['file_name'])) {
		$db->exec("UPDATE `cms_mail` SET `count` = `count`+1 WHERE `id` = '$id' LIMIT 1");
		Header('Location: ../files/mail/' . $res['file_name']); exit;
	} else {
		echo functions::display_error($lng_mail['file_does_not_exist']);
	}
} else {
	echo functions::display_error($lng_mail['file_is_not_chose']);
}