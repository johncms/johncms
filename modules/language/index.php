<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\ConfigInterface;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = App::getContainer();

/** @var ConfigInterface $config */
$config = $container->get(ConfigInterface::class);

/** @var Engine $view */
$view = $container->get(Engine::class);
$view->addFolder('language', __DIR__ . '/templates/');

echo $view->render('language::index');
