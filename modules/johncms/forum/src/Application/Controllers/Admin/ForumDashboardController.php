<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers\Admin;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Forum\Application\Services\ForumStructureRowMapper;
use Johncms\Modules\Forum\Application\UseCases\GetForumDashboardUseCase;
use Johncms\Modules\Forum\Domain\Repository\ForumStructureRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\View\ViewResponse;

/**
 * The forum in the panel: what it holds, and the whole tree of its sections to manage.
 */
final readonly class ForumDashboardController
{
    public function __construct(
        private NavChain $navChain,
        private AccessCheckerInterface $accessChecker,
        private GetForumDashboardUseCase $getDashboard,
        private ForumStructureRepositoryInterface $structure,
        private ForumStructureRowMapper $rowMapper,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $title = __('Forum');
        $this->navChain->add($title);

        return new ViewResponse('@forum/admin/index.twig', [
            'title'             => $title,
            'page_title'        => $title,
            'module_menu'       => ['forum' => true],
            'counters'          => $this->getDashboard->execute(),
            'rows'              => $this->rowMapper->map($this->structure->tree()),
            'add_category_url'  => '/admin/forum/structure/new',
            'hidden_topics_url' => '/admin/forum/hidden-topics',
            'hidden_posts_url'  => '/admin/forum/hidden-posts',
            // The settings are for the site owner only.
            'settings_url'      => $this->accessChecker->allows(CorePermissions::ADMIN_SETTINGS_MANAGE) ? '/admin/forum/settings' : '',
        ]);
    }
}
