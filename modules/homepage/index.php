<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\UserInterface;
use Johncms\Utility\NewsWidget;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = App::getContainer();

/** @var Engine $view */
$view = $container->get(Engine::class);
$view->addFolder('homepage', __DIR__ . '/templates/');

// Если нужно показать ссылку "На главную", то добавляем строку ниже
//$view->addData(['homeButton' => true]);

echo $view->render('homepage::mainmenu', [
    'counters' => $container->get('counters'),
    'news'     => new NewsWidget(),
    'rights'   => $container->get(UserInterface::class)->rights,
]);
