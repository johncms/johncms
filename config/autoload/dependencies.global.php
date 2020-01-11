<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\NavChain;
use Johncms\System\Legacy\Bbcode;
use Johncms\System\Legacy\Tools;

return [
    'dependencies' => [
        'factories' => [
            Bbcode::class   => Bbcode::class,
            NavChain::class => NavChain::class,
            Tools::class    => Tools::class,
            'counters'      => Johncms\Counters::class,
        ],
    ],
];
