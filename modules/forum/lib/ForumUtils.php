<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Forum;

use Johncms\NavChain;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;

class ForumUtils
{
    /**
     * Building breadcrumbs
     *
     * @param int $parent
     * @param string $current_item_name
     * @param string $current_item_url
     */
    public static function buildBreadcrumbs(int $parent = 0, string $current_item_name = '', string $current_item_url = ''): void
    {
        /** @var Tools $tools */
        $tools = di(Tools::class);
        /** @var NavChain $nav_chain */
        $nav_chain = di(NavChain::class);

        $tree = [];
        $tools->getSections($tree, $parent);
        foreach ($tree as $item) {
            $nav_chain->add($item['name'], '/forum/?' . ($item['section_type'] === 1 ? 'type=topics&amp;' : '') . 'id=' . $item['id']);
        }

        if (! empty($current_item_name)) {
            $nav_chain->add($current_item_name, $current_item_url);
        }
    }

    /**
     * Page not found
     */
    public static function notFound(): void
    {
        $view = di(Render::class);

        if (! headers_sent()) {
            header('HTTP/1.0 404 Not Found');
        }

        echo $view->render(
            'system::pages/result',
            [
                'title'    => __('Forum'),
                'type'     => 'alert-danger',
                'message'  => __('Topic has been deleted or does not exists'),
                'back_url' => '/forum/',
            ]
        );
        exit;
    }
}
