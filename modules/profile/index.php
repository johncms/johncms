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
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Render;
use Johncms\System\i18n\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Assets $assets
 * @var PDO $db
 * @var Tools $tools
 */

$assets = di(Assets::class);
$config = config('johncms');
$db = di(PDO::class);
$tools = di(Tools::class);

/** @var User $user */
$user = di(User::class);

/** @var Render $view */
$view = di(Render::class);

/** @var Request $request */
$request = di(Request::class);

/** @var NavChain $nav_chain */
$nav_chain = di(NavChain::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('profile', __DIR__ . '/templates/');

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('profile', __DIR__ . '/locale');

// Закрываем от неавторизованных юзеров
if (! $user->is_valid) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'   => __('User Profile'),
            'type'    => 'alert-danger',
            'message' => __('For registered users only'),
        ]
    );
    exit;
}

$id = $request->getQuery('id', 0, FILTER_SANITIZE_NUMBER_INT);
$user_id = $request->getQuery('user', $user->id, FILTER_SANITIZE_NUMBER_INT);
$act = htmlspecialchars((string) $request->getQuery('act', 'index'));
$mod = htmlspecialchars((string) $request->getQuery('mod', ''));
$page = isset($_REQUEST['page']) ? max(1, (int) $_REQUEST['page']) : 1;
$start = isset($_REQUEST['page'])
    ? ($page - 1) * (int) $user->config->kmess
    : (isset($_GET['start']) ? abs((int) $_GET['start']) : 0);

/** @var User $user_data Получаем данные пользователя */
$user_data = $user_id !== $user->id ? (new User())->find($user_id) : $user;

if (empty($user_data->id) || (! $user_data->preg && $user->rights < 7)) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => __('User Profile'),
            'type'    => 'alert-danger',
            'message' => __('This User does not exists'),
        ]
    );
    exit;
}

// Переключаем режимы работы
$mods = [
    'ban',
    'edit',
    'images',
    'guestbook',
    'karma',
    'password',
    'reset',
    'settings',
    'confirm_new_email',
];

if ($act && ($key = array_search($act, $mods, true)) !== false && file_exists(__DIR__ . '/includes/' . $mods[$key] . '.php')) {
    require __DIR__ . '/includes/' . $mods[$key] . '.php';
} else {
    pageNotFound();
}
