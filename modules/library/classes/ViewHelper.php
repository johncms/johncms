<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Library;

use Johncms\System\View\Render;

class ViewHelper
{
    private static function setUp(): Render
    {
        return di(Render::class);
    }

    public static function sectionsListAdminPanel(int $sectionId, int $sectionItemId, int $positionId, int $total): string
    {
        return self::setUp()->render(
            'libraryHelpers::sectionListAdminPanel',
            [
                'sectionId'     => $sectionId,
                'sectionItemId' => $sectionItemId,
                'positionId'    => $positionId,
                'total'         => $total,
            ]
        );
    }
}
