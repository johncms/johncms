<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Media;

use Johncms\Media\Providers\ImagesPopup;
use Simba77\EmbedMedia\Embed;
use Simba77\EmbedMedia\Providers\Youtube;

/**
 * Class MediaEmbed
 *
 * @mixin Embed
 * @package Johncms
 */
class MediaEmbed
{
    public function __invoke(): Embed
    {
        $providers = [
            new Youtube(
                [
                    'classes' => 'embed-responsive embed-responsive-16by9',
                    'styles'  => [
                        'width' => '100%',
                    ],
                ]
            ),
            new ImagesPopup(),
        ];
        return new Embed($providers);
    }
}
