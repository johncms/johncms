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
use Johncms\Api\ToolsInterface;
use Johncms\Api\UserInterface;
use League\Plates\Engine;
use Zend\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO               $db
 * @var ToolsInterface    $tools
 * @var UserInterface     $user
 * @var Engine            $view
 * @var NavChainInterface $nav_chain
 */

$db = di(PDO::class);
$tools = di(ToolsInterface::class);
$user = di(UserInterface::class);
$view = di(Engine::class);
$nav_chain = di(NavChainInterface::class);
$route = di('route');

// Register Namespace for module templates
$view->addFolder('news', __DIR__ . '/templates/');

// Register module languages
$container->get(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

// Add a section to the navigation chain
$nav_chain->add(_t('News'), '/news/');

$id = $route['id'] ?? 0;
$act = $route['action'] ?? 'index';

$actions = [
    'index',
    'add',
    'clean',
    'del',
    'edit',
];

if (($key = array_search($act, $actions)) !== false) {
    require __DIR__ . '/includes/' . $actions[$key] . '.php';
} else {
    pageNotFound();
}
