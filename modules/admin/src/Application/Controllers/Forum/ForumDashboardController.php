<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Forum;

use Johncms\Modules\Admin\Application\UseCases\GetForumDashboardUseCase;
use Johncms\NavChain;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;

final readonly class ForumDashboardController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private GetForumDashboardUseCase $getDashboard,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $title = __('Forum Management');
        $this->navChain->add($title, '/admin/forum');

        return new ViewResponse('@admin/forum.twig', [
            'title'         => $title,
            'page_title'    => $title,
            'module_menu'   => ['forum' => true],
            'counters'      => $this->getDashboard->execute(),
            'can_configure' => $this->currentUser->rights >= 9,
        ]);
    }
}
