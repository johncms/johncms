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
use Johncms\NavChain;
use Johncms\System\i18n\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');
define('_IS_HOMEPAGE', 1); // Пометка главной страницы

/**
 * @var Render $view
 * @var NavChain $nav_chain
 */

$view = di(Render::class);
$nav_chain = di(NavChain::class);
$nav_chain->showHomePage(false);

// Register Namespace for module templates
$view->addFolder('homepage', __DIR__ . '/templates/');

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('homepage', __DIR__ . '/locale');

echo $view->render('homepage::index');
