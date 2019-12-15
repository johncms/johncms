<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api;

return [
    'dependencies' => [
        'factories' => [
            Api\NavChainInterface::class        => Johncms\Utility\NavChain::class,
            'counters'                          => Johncms\Utility\Counters::class,
        ],
    ],
];
