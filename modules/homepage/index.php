<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

$act = isset($_GET['act']) ? trim($_GET['act']) : '';

if (isset($_SESSION['ref'])) {
    unset($_SESSION['ref']);
}

if (isset($_GET['err'])) {
    $act = 404;
}

switch ($act) {
    case '404':
        /** @var Johncms\Api\ToolsInterface $tools */
        $tools = App::getContainer()->get(Johncms\Api\ToolsInterface::class);

        $headmod = 'error404';
        require 'system/head.php';
        echo $tools->displayError(_t('The requested page does not exists'));
        break;

    default:
        // Главное меню сайта
        if (isset($_SESSION['ref'])) {
            unset($_SESSION['ref']);
        }
        $headmod = 'mainpage';
        require 'system/head.php';
        include 'mainmenu.php';
}

require 'system/end.php';
