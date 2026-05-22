<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Services;

use Johncms\Modules\Library\Application\Services\LibraryArticlePathService;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;
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
        $tools       = di(Tools::class);
        $nav_chain   = di(NavChain::class);
        $pathService = di(LibraryCategoryPathService::class);
        foreach ($data as $value) {
            $url = $pathService->getCategoryUrlById($value['id']) ?? '/library/';
            $nav_chain->add($tools->checkout($value['name']), $url);
        }
    }

    public static function printVote(int $id, mixed $userVote): string
    {
        $articleUrl = di(LibraryArticlePathService::class)->getArticleUrlById($id) ?? '/library/';
        return self::setUp()->render(
            'libraryHelpers::printvote',
            [
                'id'          => $id,
                'article_url' => $articleUrl,
                'userVote'    => $userVote,
            ]
        );
    }
}
