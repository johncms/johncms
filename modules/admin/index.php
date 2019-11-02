<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\UserInterface;
use Psr\Container\ContainerInterface;
use Zend\I18n\Translator\Translator;

@ini_set('max_execution_time', '600');
define('_IN_JOHNADM', 1);

$id = isset($_REQUEST['id']) ? abs((int) $_REQUEST['id']) : 0;
$act = filter_input(INPUT_GET, 'act', FILTER_SANITIZE_STRING) ?? '';
$mod = filter_input(INPUT_GET, 'mod', FILTER_SANITIZE_STRING) ?? '';
$do = filter_input(INPUT_GET, 'do', FILTER_SANITIZE_STRING) ?? '';

/** @var ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var UserInterface $user */
$user = $container->get(UserInterface::class);

/** @var League\Plates\Engine $view */
$view = $container->get(League\Plates\Engine::class);
$view->addFolder('admin', __DIR__ . '/templates/');

/** @var Translator $translator */
$translator = $container->get(Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

// Проверяем права доступа
if ($user->rights < 7) {
    exit(_t('Access denied'));
}

ob_start();

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
        'regtotal'   => $db->query("SELECT COUNT(*) FROM `users` WHERE `preg`='0'")->fetchColumn(),
        'bantotal'   => $db->query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `ban_time` > '" . time() . "'")->fetchColumn(),
        'countusers' => $container->get('counters')->users(),
        'countadm'   => $db->query("SELECT COUNT(*) FROM `users` WHERE `rights` >= '1'")->fetchColumn(),
    ]);
}
