<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Psr\Container\ContainerInterface $container
 * @var Johncms\Utility\Counters         $counters
 * @var PDO                              $db
 * @var League\Plates\Engine             $view
 */

$counters = $container->get('counters');

$count_adm = $db->query('SELECT COUNT(*) FROM `users` WHERE `rights` > 0')->fetchColumn();
$birthDays = $db->query("SELECT COUNT(*) FROM `users` WHERE `dayb` = '" . date('j') . "' AND `monthb` = '" . date('n') . "' AND `preg` = '1'")->fetchColumn();

echo $view->render('users::index', [
    'usersCount' => $counters->users(),
    'adminCount' => $count_adm,
    'birthDays'  => $birthDays,
    'albumCount' => $counters->album(),
]);
