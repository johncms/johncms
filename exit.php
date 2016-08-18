<?php

define('_IN_JOHNCMS', 1);

require('incfiles/core.php');
$referer = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : core::$system_set['homeurl'];

if (isset($_POST['submit'])) {
    setcookie('cuid', '');
    setcookie('cups', '');
    session_destroy();
    header('Location: index.php');
} else {
    require('incfiles/head.php');
    echo '<div class="rmenu">' .
        '<p>' . _t('Are you sure you want to leave the site?') . '</p>' .
        '<form action="exit.php" method="post"><p><input type="submit" name="submit" value="' . _t('Logout') . '" /></p></form>' .
        '<p><a href="' . $referer . '">' . _t('Cancel') . '</a></p>' .
        '</div>';
    require('incfiles/end.php');
}
