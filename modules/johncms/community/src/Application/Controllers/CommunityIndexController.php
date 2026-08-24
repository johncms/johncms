<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\Controllers;

use Johncms\Modules\Community\Application\UseCases\ViewIndexUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;

final readonly class CommunityIndexController
{
    public function __construct(
        private NavChain $navChain,
        private ViewIndexUseCase $viewIndexUseCase,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $title = __('Community');
        $this->navChain->add($title, '/community/');


        $result = $this->viewIndexUseCase->execute();

        return new ViewResponse(
            '@community/public/index.twig',
            [
                'title'      => $result->title,
                'page_title' => $result->pageTitle,
                'counters'   => [
                    'usersCount'    => $result->usersCount,
                    'newUsersCount' => $result->newUsersCount,
                    'adminCount'    => $result->adminCount,
                    'birthDays'     => $result->birthDays,
                ],
            ]
        );
    }
}
