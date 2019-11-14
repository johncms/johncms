<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\NavChainInterface;
use Johncms\Api\ToolsInterface;
use Johncms\Api\UserInterface;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;
use Zend\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var ContainerInterface $container
 * @var PDO                $db
 * @var ToolsInterface     $tools
 * @var UserInterface      $user
 * @var Engine             $view
 * @var NavChainInterface  $nav_chain
 */

$container = App::getContainer();
$db = $container->get(PDO::class);
$tools = $container->get(ToolsInterface::class);
$user = $container->get(UserInterface::class);
$view = $container->get(Engine::class);
$nav_chain = $container->get(NavChainInterface::class);

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('news', __DIR__ . '/templates/');

// Регистрируем языки модуля
$container->get(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

// Добавляем раздел в навигационную цепочку
$nav_chain->add(_t('News'), '/news/');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = isset($_REQUEST['do']) ? trim($_REQUEST['do']) : 'index';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

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
