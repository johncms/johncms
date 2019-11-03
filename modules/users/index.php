<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\ConfigInterface;
use Johncms\Api\ToolsInterface;
use Johncms\Api\UserInterface;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;
use Zend\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

$headmod = 'users';

/** @var ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var UserInterface $systemUser */
$systemUser = $container->get(UserInterface::class);

/** @var ConfigInterface $config */
$config = $container->get(ConfigInterface::class);

/** @var Translator $translator */
$translator = $container->get(Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

/** @var ToolsInterface $tools */
$tools = $container->get(ToolsInterface::class);

/** @var Engine $view */
$view = $container->get(Engine::class);
$view->addFolder('users', __DIR__ . '/templates/');

// Закрываем от неавторизованных юзеров
if (! $systemUser->isValid() && ! $config->active) {
    echo $view->render('system::app/old_content', [
        'content' => $tools->displayError(_t('For registered users only')),
    ]);
    exit;
}

// Переключаем режимы работы
$actions = [
    'admlist',
    'birth',
    'online',
    'search',
    'top',
    'userlist',
];

if (($key = array_search($act, $actions)) !== false) {
    require __DIR__ . '/includes/' . $actions[$key] . '.php';
} else {
    /** @var Johncms\Utility\Counters $counters */
    $counters = $container->get('counters');

    $count_adm = $db->query('SELECT COUNT(*) FROM `users` WHERE `rights` > 0')->fetchColumn();
    $birthDays = $db->query("SELECT COUNT(*) FROM `users` WHERE `dayb` = '" . date('j', time()) . "' AND `monthb` = '" . date('n', time()) . "' AND `preg` = '1'")->fetchColumn();

    echo $view->render('users::index', [
        'usersCount' => $counters->users(),
        'adminCount' => $count_adm,
        'birthDays'  => $birthDays,
        'albumCount' => $counters->album(),
    ]);
}
