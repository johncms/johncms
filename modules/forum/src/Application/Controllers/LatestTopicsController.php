<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\UseCases\ViewLatestTopicsUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;

final readonly class LatestTopicsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private ViewLatestTopicsUseCase $viewLatestTopicsUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(): ViewResponse
    {
        $result = $this->viewLatestTopicsUseCase->execute();

        $caption = __('Last 10');
        $this->navChain->add(__('Forum'), '/forum/');
        $this->navChain->add($caption);

        return new ViewResponse(
            '@forum/public/topic-list.twig',
            [
                'pagination'          => null,
                'title'               => $caption,
                'page_title'          => $caption,
                'empty_message'       => __('The list is empty'),
                'topics'              => $result->topics,
                'total'               => $result->total,
                'show_period'         => false,
                'period_action'       => '/forum/topics-period/',
                'mark_as_read_action' => '',
            ]
        );
    }
}
