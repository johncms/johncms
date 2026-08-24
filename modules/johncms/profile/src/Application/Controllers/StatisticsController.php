<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetProfileStatisticsUseCase;
use Johncms\NavChain;

final readonly class StatisticsController
{
    public function __construct(
        private NavChain $navChain,
        private GetProfileStatisticsUseCase $getProfileStatisticsUseCase,
    ) {
    }

    public function __invoke(int $id): ViewResponse
    {
        try {
            $profileUser = $this->getProfileStatisticsUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => __('Statistic'),
                    'type'    => 'alert-danger',
                    'message' => $e->getMessage(),
                ]
            );
        }

        $title = $profileUser->name . ': ' . __('Statistic');

        $this->navChain->add($profileUser->name, '/profile/' . $profileUser->id);
        $this->navChain->add(__('Statistic'));

        return new ViewResponse(
            '@profile/public/statistics.twig',
            [
                'title'        => $title,
                'page_title'   => $title,
                'profile_user' => $profileUser,
            ]
        );
    }
}
