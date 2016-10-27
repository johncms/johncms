<?php

define('_IN_JOHNCMS', 1);

require('incfiles/core.php');

$config = App::getContainer()->get('config')['johncms'];
$referer = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : $config['homeurl'];

if (isset($_POST['submit'])) {
    setcookie('cuid', '');
    setcookie('cups', '');
    session_destroy();
    header('Location: index.php');
} else {
    require('system/head.php');
    echo '<div class="rmenu">' .
        '<p>' . _t('Are you sure you want to leave the site?', 'system') . '</p>' .
        '<form action="exit.php" method="post"><p><input type="submit" name="submit" value="' . _t('Logout', 'system') . '" /></p></form>' .
        '<p><a href="' . $referer . '">' . _t('Cancel', 'system') . '</a></p>' .
        '</div>';
    require('system/end.php');
}
