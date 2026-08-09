<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Help\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;

final readonly class ForumRulesController
{
    public function __construct(
        private NavChain $navChain,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $title = __('Forum rules');

        $this->navChain->add(__('Information, FAQ'), '/help/');
        $this->navChain->add($title);

        return new ViewResponse(
            '@help/public/forum-rules.twig',
            [
                'title'      => $title,
                'page_title' => $title,
                'back_url'   => '/help/',
            ]
        );
    }
}
