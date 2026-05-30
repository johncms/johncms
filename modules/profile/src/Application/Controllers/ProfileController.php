<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetProfileViewUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class ProfileController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetProfileViewUseCase $getProfileViewUseCase,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function __invoke(int $id): string
    {
        try {
            $profile = $this->getProfileViewUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'   => __('User Profile'),
                    'type'    => 'alert-danger',
                    'message' => $e->getMessage(),
                ]
            );
        }

        $this->navChain->add($profile->title, '/profile/' . $id);

        $this->render->addData([
            'title'      => $profile->title,
            'page_title' => $profile->title,
        ]);

        return $this->render->render(
            'profile::view',
            [
                'data' => [
                    'user'              => $profile->user,
                    'show_ip'           => $profile->showIp,
                    'can_write'         => $profile->canWrite,
                    'blocked'           => $profile->blocked,
                    'notifications'     => $profile->notifications,
                    'active_ban'        => $profile->activeBan,
                    'active_ban_reason' => $profile->activeBanReason,
                    'counters'          => $profile->counters,
                    'buttons'           => $profile->buttons,
                ],
            ]
        );
    }
}
