<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\NavChain;
use Johncms\System\i18n\Translator;
use Johncms\System\Legacy\Tools;
use Johncms\System\Users\User;
use Johncms\System\View\Render;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var PDO $db */
$db = di(PDO::class);

/** @var Johncms\Counters $counters */
$counters = di('counters');

/** @var Render $view */
$view = di(Render::class);

/** @var User $user */
$user = di(User::class);

/** @var NavChain $nav_chain */
$nav_chain = di(NavChain::class);

/** @var Tools $tools */
$tools = di(Tools::class);

$route = di('route');

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('notifications', __DIR__ . '/templates/');

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('notifications', __DIR__ . '/locale');

$nav_chain->add(__('Notifications'));

// Список доступных страниц
$pages = [
    'index'    => 'index.php',
    'settings' => 'settings.php',
    'clear'    => 'clear.php',
];

// Определяем наличие страницы и показываем если она есть
$action = $route['action'] ?? 'index';
if (array_key_exists($action, $pages)) {
    require __DIR__ . '/includes/' . $pages[$action];
} else {
    pageNotFound();
}
