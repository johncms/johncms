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
use Johncms\System\Users\User;
use Johncms\System\View\Render;
use Johncms\System\i18n\Translator;

@ini_set('max_execution_time', '600');
define('_IN_JOHNADM', 1);

/**
 * @var Render $view
 * @var PDO $db
 * @var Tools $tools
 * @var User $user
 * @var NavChain $nav_chain
 */

$db = di(PDO::class);
$tools = di(Tools::class);
$user = di(User::class);
$view = di(Render::class);
$route = di('route');
$nav_chain = di(NavChain::class);
$request = di(Request::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('admin', __DIR__ . '/templates/');

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('admin', __DIR__ . '/locale');

module_lib_loader('admin');

$id = isset($_REQUEST['id']) ? abs((int) $_REQUEST['id']) : 0;
$act = $route['action'] ?? 'index';
$mod = filter_input(INPUT_GET, 'mod', FILTER_SANITIZE_STRING) ?? '';
$do = filter_input(INPUT_GET, 'do', FILTER_SANITIZE_STRING) ?? '';

$nav_chain->add(__('Admin Panel'), '/admin/');

// Проверяем права доступа
if ($user->rights < 7) {
    exit(__('Access denied'));
}

$actions = [
    'access',
    'adminlist',
    'ads',
    'antiflood',
    'antispy',
    'ban_panel',
    'counters',
    'emoticons',
    'forum',
    'index',
    'ip_whois',
    'ipban',
    'karma',
    'languages',
    'mail',
    'news',
    'reg',
    'search_ip',
    'settings',
    'userlist',
    'usr_clean',
    'usr_del',
];

$view->addData(
    [
        'regtotal'   => $db->query("SELECT COUNT(*) FROM `users` WHERE `preg`='0'")->fetchColumn(),
        'countusers' => $db->query("SELECT COUNT(*) FROM `users` WHERE `preg`='1'")->fetchColumn(),
        'countadm'   => $db->query("SELECT COUNT(*) FROM `users` WHERE `rights` >= '1'")->fetchColumn(),
        'bantotal'   => $db->query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `ban_time` > '" . time() . "'")->fetchColumn(),
    ],
    [
        'admin::sidebar-admin-menu',
    ]
);

if (($key = array_search($act, $actions)) !== false) {
    require __DIR__ . '/includes/' . $actions[$key] . '.php';
} else {
    pageNotFound();
}
