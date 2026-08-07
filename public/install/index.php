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
use Johncms\Http\Request;
use Johncms\Http\RequestFactory;
use Johncms\System\i18n\Translator;
use Johncms\View\Twig\TwigRenderer;

// Check the current PHP version
if (PHP_VERSION_ID < 80200) {
    die('<div style="text-align: center; font-size: xx-large"><strong>ERROR!</strong><br>Your needs PHP 8.2 or higher</div>');
}

// Resolved from __DIR__: this line runs before the constants are defined.
require dirname(__DIR__, 2) . '/vendor/autoload.php';

// Load the configuration
$config = (new \Johncms\Config\ConfigLoader(CONFIG_PATH . 'autoload'))->load();
\Johncms\Config\ConfigRepository::init($config);

// The installer is reachable by anyone before the site exists, so it needs the same error
// handling as the front controller: failures are logged, and their details are shown only
// when DEBUG allows it. Without this an uncaught exception here is governed by php.ini
// display_errors and can dump a stack trace to the visitor.
$container = \Johncms\Container\PSRContainerFactory::getContainer();
(new \Johncms\Logs\GlobalErrorHandler(
    logger:    $container->get(\Psr\Log\LoggerInterface::class),
    container: $container
))->registerHandlers();

session_name('SESID');
session_start();

// The installer runs before the application exists, so it builds its own request instead of
// asking the container for one. The steps below are included into this scope and use it.
/** @var Request $request */
$request = $container->get(RequestFactory::class)($container);

$translator = new Translator();
$translator->setLocale($_SESSION['lng'] ?? 'en');
$translator->addTranslationDomain('install', __DIR__ . '/locale');
$translator->defaultDomain('install');
TranslatorFunctions::register($translator);

// The installer has an environment of its own: there is no site yet, so nothing of a visitor,
// a csrf token or the installed modules is available to its templates.
$view = new TwigRenderer($container->get('johncms.twig.install'));

// Shared by every step; a step adds its own title and data to this.
$viewData = ['locale' => $translator->getLocale()];

$loader = new Aura\Autoload\Loader();
$loader->register();
$loader->addPrefix('Install', __DIR__ . '/lib');

$current_step = $request->queryInt('step', 1);

if (
    $current_step !== 5
    && is_file(CONFIG_PATH . 'autoload/database.local.php')
    && is_file(CONFIG_PATH . 'autoload/system.local.php')
) {
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

$viewData += ['current_step' => $current_step, 'steps' => $steps, 'cms_version' => CMS_VERSION];

switch ($current_step) {
    case 5:
        require __DIR__ . '/steps/step_5.php';
        break;

    case 4:
        require __DIR__ . '/steps/step_4.php';
        break;

    case 3:
        require __DIR__ . '/steps/step_3.php';
        break;

    case 2:
        require __DIR__ . '/steps/step_2.php';
        break;

    default:
        require __DIR__ . '/steps/step_1.php';
}
