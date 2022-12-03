<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Http\Request;
use Johncms\Install\Install;
use Johncms\View\Render;

ini_set('max_execution_time', '600');

require '../../system/vendor/autoload.php';

$request = di(Request::class);

(new Install())->init();

$currentStep = $request->getQuery('step', 1, FILTER_VALIDATE_INT);

if ($currentStep !== 5 && is_file('../config/autoload/database.local.php') && is_file('../config/autoload/system.local.php')) {
    die('<div style="text-align: center; font-size: xx-large"><strong>ERROR!</strong><br>The system is already installed</div>');
}

$steps = [
    [
        'name'    => __('Preparing for installation'),
        'active'  => ($currentStep > 1),
        'current' => ($currentStep === 1),
    ],
    [
        'name'    => __('Checking parameters'),
        'active'  => ($currentStep > 2),
        'current' => ($currentStep === 2),
    ],
    [
        'name'    => __('Database'),
        'active'  => ($currentStep > 3),
        'current' => ($currentStep === 3),
    ],
    [
        'name'    => __('Setting'),
        'active'  => ($currentStep > 4),
        'current' => ($currentStep === 4),
    ],
    [
        'name'    => __('Completion'),
        'active'  => ($currentStep > 5),
        'current' => ($currentStep === 5),
    ],
];

$render = di(Render::class);
$render->addData(['current_step' => $currentStep, 'steps' => $steps]);

switch ($currentStep) {
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
