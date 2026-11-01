<?php

declare(strict_types=1);

namespace Johncms\Http\Controller;

use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;

final readonly class StyleGuideController
{
    public function __construct(private NavChain $navChain)
    {
    }

    public function __invoke(): ViewResponse
    {
        $title = d__('system', 'Style guide');
        $this->navChain->add($title);

        return new ViewResponse('@theme/pages/style-guide.twig', [
            'title' => $title,
            'page_title' => $title,
        ]);
    }
}
