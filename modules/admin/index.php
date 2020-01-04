<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\System\Utility\Tools;
use Johncms\System\Users\User;
use Johncms\System\View\Render;
use Laminas\I18n\Translator\Translator;

@ini_set('max_execution_time', '600');
define('_IN_JOHNADM', 1);

/**
 * @var Render $view
 * @var PDO $db
 * @var Tools $tools
 * @var User $user
 */

$db = di(PDO::class);
$tools = di(Tools::class);
$user = di(User::class);
$view = di(Render::class);
$route = di('route');

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('admin', __DIR__ . '/templates/');

// Регистрируем папку с языками модуля
di(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

$id = isset($_REQUEST['id']) ? abs((int) $_REQUEST['id']) : 0;
$act = $route['action'] ?? 'index';
$mod = filter_input(INPUT_GET, 'mod', FILTER_SANITIZE_STRING) ?? '';
$do = filter_input(INPUT_GET, 'do', FILTER_SANITIZE_STRING) ?? '';

// Проверяем права доступа
if ($user->rights < 7) {
    exit(_t('Access denied'));
}

$actions = [
    'index',
    'forum',
    'news',
    'ads',
    'counters',
    'ip_whois',
    'languages',
    'settings',
    'smilies',
    'access',
    'antispy',
    'httpaf',
    'ipban',
    'antiflood',
    'ban_panel',
    'karma',
    'reg',
    'mail',
    'search_ip',
    'userlist',
    'adminlist',
    'usr_clean',
    'usr_del',
    'social_setting',
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
