<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\UseCases\ViewLatestTopicsUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class LatestTopicsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private ViewLatestTopicsUseCase $viewLatestTopicsUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(): string
    {
        $result = $this->viewLatestTopicsUseCase->execute();

        $caption = __('Last 10');
        $this->navChain->add(__('Forum'), '/forum/');
        $this->navChain->add($caption);

        return $this->render->render(
            'forum::new_topics',
            [
                'pagination'    => '',
                'title'         => $caption,
                'page_title'    => $caption,
                'empty_message' => __('The list is empty'),
                'topics'        => $result->topics,
                'total'         => $result->total,
                'show_period'   => false,
            ]
        );
    }
}
