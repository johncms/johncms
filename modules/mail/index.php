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

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Tools $tools
 * @var User $user
 * @var Render $view
 */

$config = di('config')['johncms'];
$db = di(PDO::class);
$tools = di(Tools::class);
$user = di(User::class);
$view = di(Render::class);

/** @var NavChain $nav_chain */
$nav_chain = di(NavChain::class);

/** @var Request $request */
$request = di(Request::class);

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('mail', __DIR__ . '/locale');

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('mail', __DIR__ . '/templates/');

$id = $request->getQuery('id', 0, FILTER_SANITIZE_NUMBER_INT);
$act = $request->getQuery('act', 'index', FILTER_SANITIZE_STRING);
$mod = $request->getQuery('mod', '', FILTER_SANITIZE_STRING);

if (isset($_SESSION['ref'])) {
    unset($_SESSION['ref']);
}

//Проверка авторизации
if (! $user->isValid()) {
    header('Location: ' . $config['homeurl']);
    exit;
}

function formatsize($size)
{
    // Форматирование размера файлов
    if ($size >= 1073741824) {
        $size = round($size / 1073741824 * 100) / 100 . ' Gb';
    } elseif ($size >= 1048576) {
        $size = round($size / 1048576 * 100) / 100 . ' Mb';
    } elseif ($size >= 1024) {
        $size = round($size / 1024 * 100) / 100 . ' Kb';
    } else {
        $size .= ' b';
    }

    return $size;
}

$title = __('Mail');

// Добавляем раздел в навигационную цепочку
$nav_chain->add(__('My Account'), '/profile/?act=office');

// Массив подключаемых функций
$mods = [
    'ignor',
    'write',
    'deluser',
    'load',
    'files',
    'input',
    'output',
    'delete',
    'index',
];

//Проверка выбора функции
if ($act && ($key = array_search($act, $mods, true)) !== false && file_exists(__DIR__ . '/includes/' . $mods[$key] . '.php')) {
    require __DIR__ . '/includes/' . $mods[$key] . '.php';
} else {
    pageNotFound();
}
