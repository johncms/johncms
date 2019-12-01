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
use Johncms\View\Render;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var ConfigInterface    $config
 * @var Render             $view
 */

$config = di(ConfigInterface::class);
$view = di(Render::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('language', __DIR__ . '/templates/');

echo $view->render('language::index');
