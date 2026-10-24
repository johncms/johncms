<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\System;

use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;

/**
 * The style guide of the panel: the colors, the type and every component, rendered live from the
 * bundle the panel loads, each with the markup that produces it.
 */
final readonly class StyleGuideController
{
    public function __construct(
        private NavChain $navChain,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $title = __('Style guide');
        $this->navChain->add($title);

        return new ViewResponse(
            '@admin/style-guide.twig',
            [
                'title'      => $title,
                'page_title' => $title,
                'sys_menu'   => ['style_guide' => true],
            ]
        );
    }
}
