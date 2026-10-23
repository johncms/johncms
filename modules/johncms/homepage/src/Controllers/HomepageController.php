<?php

declare(strict_types=1);

namespace Johncms\Modules\Homepage\Controllers;

use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\View\Homepage\HomepageBlocks;

final readonly class HomepageController
{
    public function __construct(
        private NavChain $navChain,
        private HomepageBlocks $blocks,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        // Marks the request, not the process: Ads and Counters read the flag from here to pick
        // what belongs on the home page. It used to be the _IS_HOMEPAGE constant, which cannot be
        // unset — under a long-running runtime every page served after the home page inherited it.
        $request->attributes->set('is_homepage', true);
        $this->navChain->showHomePage(false);

        $config = config('johncms');

        return new ViewResponse(
            '@homepage/public/index.twig',
            [
                'canonical'   => $config['homeurl'] . '/',
                'title'       => $config['meta_title'] ?? '',
                'keywords'    => $config['meta_key'],
                'description' => $config['meta_desc'],
                'blocks'      => $this->blocks->all(),
            ]
        );
    }
}
