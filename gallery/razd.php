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

if ($rights >= 6) {
    if (isset($_POST['submit'])) {
        $text = isset($_POST['text']) ? functions::checkin($_POST['text']) : '';
        if ($text) {
            $stmt = $db->prepare("INSERT INTO `gallery` SET 
                `refid` = '0',
                `time`  = '" . time() . "',
                `type`  = 'rz',
                `avtor` = '',
                `text`  = ?,
                `name`  = ''
            ");
            $db->execute([
                $text
            ]);
            header("location: index.php"); exit;
        } else {
            echo functions::display_error($lng['error_empty_fields']);
        }
    } else {
        echo '<div class="phdr"><b>' . $lng_gal['create_section'] . '</b></div>' .
            '<form action="index.php?act=razd" method="post">' .
            '<div class="gmenu">' .
            '<p>' . $lng['name'] . ':<br/><input type="text" name="text"/></p>' .
            '<p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p>' .
            '</div></form>' .
            '<div class="phdr"><a href="index.php">' . $lng['back'] . '</a></div>';
    }
}