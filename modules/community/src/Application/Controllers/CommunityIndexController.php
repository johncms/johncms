<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\Controllers;

use Johncms\Modules\Community\Application\UseCases\ViewIndexUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Users\User;

final readonly class CommunityIndexController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private ViewIndexUseCase $viewIndexUseCase,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $title = __('Community');
        $this->navChain->add($title, '/community/');

        $config = config('johncms');
        if (! $config['active'] && ! $this->currentUser->isValid()) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => $title,
                    'type'    => 'alert-danger',
                    'message' => __('For registered users only'),
                ]
            );
        }

        $result = $this->viewIndexUseCase->execute();

        return new ViewResponse(
            '@community/public/index.twig',
            [
                'title'      => $result->title,
                'page_title' => $result->pageTitle,
                'counters'   => [
                    'usersCount' => $result->usersCount,
                    'adminCount' => $result->adminCount,
                    'birthDays'  => $result->birthDays,
                ],
            ]
        );
    }
}
