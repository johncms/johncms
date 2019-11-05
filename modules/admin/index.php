<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\ToolsInterface;
use Johncms\Api\UserInterface;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;
use Zend\I18n\Translator\Translator;

@ini_set('max_execution_time', '600');
define('_IN_JOHNADM', 1);

/**
 * @var ContainerInterface $container
 * @var Engine             $view
 * @var PDO                $db
 * @var ToolsInterface     $tools
 * @var UserInterface      $user
 */

$container = App::getContainer();
$db = $container->get(PDO::class);
$tools = $container->get(ToolsInterface::class);
$user = $container->get(UserInterface::class);
$view = $container->get(Engine::class);
$view->addFolder('admin', __DIR__ . '/templates/');

// Регистрируем языки модуля
$container->get(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

$id = isset($_REQUEST['id']) ? abs((int) $_REQUEST['id']) : 0;
$act = filter_input(INPUT_GET, 'act', FILTER_SANITIZE_STRING) ?? '';
$mod = filter_input(INPUT_GET, 'mod', FILTER_SANITIZE_STRING) ?? '';
$do = filter_input(INPUT_GET, 'do', FILTER_SANITIZE_STRING) ?? '';

// Проверяем права доступа
if ($user->rights < 7) {
    exit(_t('Access denied'));
}

$actions = [
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
    'usr',
    'usr_adm',
    'usr_clean',
    'usr_del',
    'social_setting',
];

if (($key = array_search($act, $actions)) !== false) {
    require __DIR__ . '/includes/' . $actions[$key] . '.php';
} else {
    echo $view->render('admin::index', [
        'countusers' => $db->query("SELECT COUNT(*) FROM `users` WHERE `preg`='1'")->fetchColumn(),
        'countadm'   => $db->query("SELECT COUNT(*) FROM `users` WHERE `rights` >= '1'")->fetchColumn(),
        'regtotal'   => $db->query("SELECT COUNT(*) FROM `users` WHERE `preg`='0'")->fetchColumn(),
        'bantotal'   => $db->query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `ban_time` > '" . time() . "'")->fetchColumn(),
    ]);
}
