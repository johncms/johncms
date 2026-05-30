<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetProfileStatisticsUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class StatisticsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetProfileStatisticsUseCase $getProfileStatisticsUseCase,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function __invoke(int $id): string
    {
        try {
            $profileUser = $this->getProfileStatisticsUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->render->render(
                'system::pages/result',
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

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'profile::statistics',
            [
                'profile_user' => $profileUser,
            ]
        );
    }
}
