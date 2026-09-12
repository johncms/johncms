<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers\Admin;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Forum\Application\UseCases\GetForumDashboardUseCase;
use Johncms\NavChain;
use Johncms\Http\View\ViewResponse;

final readonly class ForumDashboardController
{
    public function __construct(
        private NavChain $navChain,
        private AccessCheckerInterface $accessChecker,
        private GetForumDashboardUseCase $getDashboard,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $title = __('Forum Management');
        $this->navChain->add($title, '/admin/forum');

        return new ViewResponse('@forum/admin/dashboard.twig', [
            'title'         => $title,
            'page_title'    => $title,
            'module_menu'   => ['forum' => true],
            'counters'      => $this->getDashboard->execute(),
            'can_configure' => $this->accessChecker->allows(CorePermissions::ADMIN_SETTINGS_MANAGE),
        ]);
    }
}
