<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\System\Http\Request;
use Johncms\System\Users\User;
use Johncms\System\Utility\Tools;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Render;
use Johncms\Utility\NavChain;
use Laminas\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Assets $assets
 * @var PDO $db
 * @var Tools $tools
 * @var User $user
 * @var Render $view
 */

$assets = di(Assets::class);
$config = di('config')['johncms'];
$db = di(PDO::class);
$route = di('route');
$tools = di(Tools::class);
$user = di(User::class);
$view = di(Render::class);

/** @var NavChain $nav_chain */
$nav_chain = di(NavChain::class);

/** @var Request $request */
$request = di(Request::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('users', __DIR__ . '/templates/');

// Регистрируем папку с языками модуля
di(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = $route['action'] ?? 'index';
$mod = $route['mod'] ?? '';

$title = _t('Community');

$nav_chain->add($title, '/community/');

// Закрываем от неавторизованных юзеров
if (! $config['active'] && ! $user->isValid()) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => _t('For registered users only'),
        ]
    );
    exit;
}

// Переключаем режимы работы
$actions = [
    'administration',
    'birthdays',
    'index',
    'search',
    'top',
    'users',
];

if (($key = array_search($act, $actions)) !== false) {
    require __DIR__ . '/includes/' . $actions[$key] . '.php';
} else {
    pageNotFound();
}
