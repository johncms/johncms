<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\System\Legacy\Tools;
use Johncms\System\Users\User;
use Johncms\System\View\Render;
use Johncms\NavChain;
use Laminas\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Tools $tools
 * @var User $user
 * @var Render $view
 * @var NavChain $nav_chain
 */

$db = di(PDO::class);
$tools = di(Tools::class);
$user = di(User::class);
$view = di(Render::class);
$nav_chain = di(NavChain::class);
$route = di('route');

// Register Namespace for module templates
$view->addFolder('news', __DIR__ . '/templates/');

// Register module languages
$container->get(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/news.mo');

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
