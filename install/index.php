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
use Johncms\System\View\Render;

// Check the current PHP version
if (PHP_VERSION_ID < 70200) {
    die('<div style="text-align: center; font-size: xx-large"><strong>ERROR!</strong><br>Your needs PHP 7.2 or higher</div>');
}

require '../system/vendor/autoload.php';

session_name('SESID');
session_start();

/** @var Request $request */
$request = di(Request::class);

/** @var Render $view */
$view = di(Render::class);

$translator = new Translator();
$translator->setLocale($_SESSION['lng'] ?? 'en');
$translator->addTranslationDomain('system', ROOT_PATH . 'system/locale');
$translator->defaultDomain('system');
TranslatorFunctions::register($translator);

/** @var Render $view */
$view = di(Render::class);
$view->addFolder('install', __DIR__ . '/templates/');

$loader = new Aura\Autoload\Loader();
$loader->register();
$loader->addPrefix('Install', __DIR__ . '/lib');

$current_step = $request->getQuery('step', 1, FILTER_VALIDATE_INT);

$steps = [
    [
        'name'    => 'Подготовка к установке',
        'active'  => ($current_step > 1),
        'current' => ($current_step === 1),
    ],
    [
        'name'    => 'Проверка параметров',
        'active'  => ($current_step > 2),
        'current' => ($current_step === 2),
    ],
    [
        'name'    => 'Базы данных',
        'active'  => ($current_step > 3),
        'current' => ($current_step === 3),
    ],
    [
        'name'    => 'Настройка',
        'active'  => ($current_step > 4),
        'current' => ($current_step === 4),
    ],
];

$view->addData(['current_step' => $current_step, 'steps' => $steps]);


switch ($current_step) {
    case 4:
        require 'steps/step_1.php';
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
