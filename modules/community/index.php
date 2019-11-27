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
use Johncms\View\Extension\Assets;
use Mobicms\Render\Engine;
use Zend\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Assets          $assets
 * @var ConfigInterface $config
 * @var PDO             $db
 * @var ToolsInterface  $tools
 * @var UserInterface   $user
 * @var Engine          $view
 */

$assets = di(Assets::class);
$config = di(ConfigInterface::class);
$db = di(PDO::class);
$route = di('route');
$tools = di(ToolsInterface::class);
$user = di(UserInterface::class);
$view = di(Engine::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('users', __DIR__ . '/templates/');

// Регистрируем папку с языками модуля
di(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = $route['action'] ?? 'index';
$mod = $route['mod'] ?? '';

// Закрываем от неавторизованных юзеров
if (! $config->active && ! $user->isValid()) {
    echo $view->render('system::app/old_content', [
        'content' => $tools->displayError(_t('For registered users only')),
    ]);
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
