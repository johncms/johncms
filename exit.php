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

require('incfiles/core.php');
$referer = isset($_SERVER['HTTP_REFERER']) ? _e($_SERVER['HTTP_REFERER']) : core::$system_set['homeurl'];

if (isset($_POST['submit'])) {
    setcookie('cuid', '');
    setcookie('cups', '');
    session_destroy();
    header('Location: index.php'); exit;
} else {
    require('incfiles/head.php');
    echo'<div class="rmenu">' .
        '<p>Вы действительно хотите покинуть сайт?</p>' .
        '<form action="exit.php" method="post"><p><input type="submit" name="submit" value="' . core::$lng['exit'] . '" /></p></form>' .
        '<p><a href="' . $referer . '">' . core::$lng['cancel'] . '</a></p>' .
        '</div>';
    require('incfiles/end.php');
}