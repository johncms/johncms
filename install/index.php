<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Gettext\TranslatorFunctions;
use Johncms\System\Http\Request;
use Johncms\System\i18n\Translator;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Render;

// Check the current PHP version
if (PHP_VERSION_ID < 70300) {
    die('<div style="text-align: center; font-size: xx-large"><strong>ERROR!</strong><br>Your needs PHP 7.3 or higher</div>');
}

require '../system/vendor/autoload.php';

session_name('SESID');
session_start();

/** @var Request $request */
$request = di(Request::class);

$translator = new Translator();
$translator->setLocale($_SESSION['lng'] ?? 'en');
$translator->addTranslationDomain('install', ROOT_PATH . 'install/locale');
$translator->defaultDomain('install');
TranslatorFunctions::register($translator);

// Подключаем шаблонизатор
$view = new Render('phtml');
$view->setTheme('default');
$view->addFolder('system', realpath(THEMES_PATH . 'default/templates/system'));
$view->loadExtension(di(Assets::class));
$view->addData(
    [
        'locale' => $translator->getLocale(),
    ]
);
$view->addFolder('install', __DIR__ . '/templates/');

$loader = new Aura\Autoload\Loader();
$loader->register();
$loader->addPrefix('Install', __DIR__ . '/lib');

$current_step = $request->getQuery('step', 1, FILTER_VALIDATE_INT);

if ($current_step !== 5 && is_file('../config/autoload/database.local.php') && is_file('../config/autoload/system.local.php')) {
    die('<div style="text-align: center; font-size: xx-large"><strong>ERROR!</strong><br>The system is already installed</div>');
}

$steps = [
    [
        'name'    => __('Preparing for installation'),
        'active'  => ($current_step > 1),
        'current' => ($current_step === 1),
    ],
    [
        'name'    => __('Checking parameters'),
        'active'  => ($current_step > 2),
        'current' => ($current_step === 2),
    ],
    [
        'name'    => __('Database'),
        'active'  => ($current_step > 3),
        'current' => ($current_step === 3),
    ],
    [
        'name'    => __('Setting'),
        'active'  => ($current_step > 4),
        'current' => ($current_step === 4),
    ],
    [
        'name'    => __('Completion'),
        'active'  => ($current_step > 5),
        'current' => ($current_step === 5),
    ],
];

$view->addData(['current_step' => $current_step, 'steps' => $steps]);

switch ($current_step) {
    case 5:
        require 'steps/step_5.php';
        break;

    case 4:
        require 'steps/step_4.php';
        break;

    case 3:
        require 'steps/step_3.php';
        break;

    case 2:
        require 'steps/step_2.php';
        break;

    default:
        require 'steps/step_1.php';
}
