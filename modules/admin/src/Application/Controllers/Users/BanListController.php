<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Admin\Application\Services\BanListRowMapper;
use Johncms\Modules\Admin\Application\UseCases\GetBanListUseCase;
use Johncms\Modules\Admin\Domain\Enums\BanListSort;
use Johncms\Modules\Admin\Domain\Enums\UserRights;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class BanListController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private GetBanListUseCase $getBanListUseCase,
        private BanListRowMapper $rowMapper,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function __invoke(?string $sort = null): string
    {
        $sortMode = $sort === 'by-violations' ? BanListSort::VIOLATIONS : BanListSort::TIME;
        $baseUrl = $sortMode === BanListSort::VIOLATIONS ? '/admin/bans/by-violations' : '/admin/bans';

        $page = max(1, (int) $this->request->getQuery('page', 1));
        $perPage = $this->currentUser->config->kmess;

        $bans = $this->getBanListUseCase->execute($sortMode, $page, $perPage);
        $total = $bans->total();

        $title = __('Ban Panel');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $page);
        $this->render->addData(
            [
                'title'      => $meta->title,
                'page_title' => $title,
                'usr_menu'   => ['ban_panel' => true],
            ]
        );

        return $this->render->render(
            'admin::ban_panel',
            [
                'items'        => $this->rowMapper->mapMany($bans->getCollection()),
                'total'        => $total,
                'per_page'     => $perPage,
                'filters'      => [
                    ['name' => __('Term'), 'url' => '/admin/bans', 'active' => $sortMode === BanListSort::TIME],
                    ['name' => __('Violations'), 'url' => '/admin/bans/by-violations', 'active' => $sortMode === BanListSort::VIOLATIONS],
                ],
                'show_amnesty' => $this->currentUser->rights === UserRights::SUPER_ADMIN->value,
                'amnesty_url'  => '/admin/bans/amnesty',
                'pagination'   => $this->tools->displayPagination(
                    $baseUrl . '?',
                    ($page - 1) * $perPage,
                    $total,
                    $perPage
                ),
            ]
        );
    }
}
