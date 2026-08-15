<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Admin\Application\Services\BanListRowMapper;
use Johncms\Modules\Admin\Application\UseCases\GetBanListUseCase;
use Johncms\Modules\Admin\Domain\Enums\BanListSort;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;

final readonly class BanListController
{
    public function __construct(
        private NavChain $navChain,
        private AccessCheckerInterface $accessChecker,
        private GetBanListUseCase $getBanListUseCase,
        private BanListRowMapper $rowMapper,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(?string $sort = null): ViewResponse
    {
        $sortMode = $sort === 'by-violations' ? BanListSort::VIOLATIONS : BanListSort::TIME;

        $pagination = $this->paginationFactory->create($this->getBanListUseCase->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $bans = $this->getBanListUseCase->getPage($sortMode, $pagination->getPerPage(), $pagination->getOffset());

        $title = __('Ban Panel');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $pagination->getCurrentPage());

        return new ViewResponse(
            '@admin/banned-users.twig',
            [
                'title'        => $meta->title,
                'page_title'   => $title,
                'usr_menu'     => ['ban_panel' => true],
                'items'        => $this->rowMapper->mapMany($bans),
                'total'        => $pagination->getTotal(),
                'per_page'     => $pagination->getPerPage(),
                'filters'      => [
                    ['name' => __('Term'), 'url' => '/admin/bans', 'active' => $sortMode === BanListSort::TIME],
                    ['name' => __('Violations'), 'url' => '/admin/bans/by-violations', 'active' => $sortMode === BanListSort::VIOLATIONS],
                ],
                'show_amnesty' => $this->accessChecker->allows(CorePermissions::ADMIN_SETTINGS_MANAGE),
                'amnesty_url'  => '/admin/bans/amnesty',
                'pagination'   => $pagination->render(),
            ]
        );
    }
}
