<?php

define('_IN_JOHNCMS', 1);

require('incfiles/core.php');

if (isset($_SESSION['ref'])) {
    unset($_SESSION['ref']);
}
if (isset($_GET['err'])) {
    $act = 404;
}

switch ($act) {
    case '404':
        // Сообщение об ошибке 404
        $headmod = 'error404';
        require('incfiles/head.php');
        echo functions::display_error($lng['error_404']);
        break;

    default:
        // Главное меню сайта
        if (isset($_SESSION['ref'])) {
            unset($_SESSION['ref']);
        }
        $headmod = 'mainpage';
        require('incfiles/head.php');
        include 'pages/mainmenu.php';
}

require('incfiles/end.php');
