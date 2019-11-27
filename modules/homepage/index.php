<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\NavChainInterface;
use Mobicms\Render\Engine;
use Zend\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Engine                   $view
 * @var Johncms\Utility\NavChain $nav_chain
 */

$view = di(Engine::class);
$nav_chain = di(NavChainInterface::class);
$nav_chain->showHomePage(false);

// Register Namespace for module templates
$view->addFolder('homepage', __DIR__ . '/templates/');

// Register the module languages folder
di(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

echo $view->render('homepage::index');
