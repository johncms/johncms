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

use Johncms\NavChain;
use Johncms\System\Legacy\Tools;
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

    public static function printNavPanel(array $data): void
    {
        $tools = di(Tools::class);
        $nav_chain = di(NavChain::class);
        foreach ($data as $key => $value) {
            $nav_chain->add($tools->checkout($value['name']), '/library/?do=dir&id=' . $value['id']);
        }
    }

    public static function printVote(int $id, $userVote): string
    {
        return self::setUp()->render(
            'libraryHelpers::printvote',
            [
                'id'       => $id,
                'userVote' => $userVote,
            ]
        );
    }
}
