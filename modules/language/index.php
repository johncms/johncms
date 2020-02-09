<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\System\View\Render;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var Render $view */
$view = di(Render::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('language', __DIR__ . '/templates/');

echo $view->render('language::index');
