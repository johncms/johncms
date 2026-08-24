<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetProfileViewUseCase;
use Johncms\NavChain;

final readonly class ProfileController
{
    public function __construct(
        private NavChain $navChain,
        private GetProfileViewUseCase $getProfileViewUseCase,
    ) {
    }

    public function __invoke(int $id): ViewResponse
    {
        try {
            $profile = $this->getProfileViewUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => __('User Profile'),
                    'type'    => 'alert-danger',
                    'message' => $e->getMessage(),
                ]
            );
        }

        $this->navChain->add($profile->title, '/profile/' . $id);

        return new ViewResponse(
            '@profile/public/view.twig',
            [
                'title'             => $profile->title,
                'page_title'        => $profile->title,
                'user'              => $profile->user,
                'show_ip'           => $profile->showIp,
                'can_write'         => $profile->canWrite,
                'blocked'           => $profile->blocked,
                'notifications'     => $profile->notifications,
                'active_ban'        => $profile->activeBan,
                'active_ban_reason' => $profile->activeBanReason,
                'counters'          => $profile->counters,
                'buttons'           => $profile->buttons,
                'karma_enabled'     => (bool) config('johncms.karma.on'),
            ]
        );
    }
}
