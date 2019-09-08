<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

define('_IN_JOHNCMS', 1);

require('system/bootstrap.php');

/** @var Johncms\Api\ConfigInterface $config */
$config = App::getContainer()->get(Johncms\Api\ConfigInterface::class);

$referer = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : $config->homeurl;

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
