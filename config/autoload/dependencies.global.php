<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Intervention\Image\ImageManager;
use Johncms\Ads;
use Johncms\AdsFactory;
use Johncms\ImageManagerFactory;
use Johncms\Mail\MailFactory;
use Johncms\NavChain;
use Johncms\Security\Csrf;
use Johncms\System\Legacy\Bbcode;
use Johncms\System\Legacy\Tools;

return [
    'dependencies' => [
        'factories' => [
            Bbcode::class       => Bbcode::class,
            NavChain::class     => NavChain::class,
            ImageManager::class => ImageManagerFactory::class,
            Ads::class          => AdsFactory::class,
            Tools::class        => Tools::class,
            Csrf::class         => Csrf::class,
            'counters'          => Johncms\CountersFactory::class,
            MailFactory::class  => MailFactory::class,
        ],
    ],
];
