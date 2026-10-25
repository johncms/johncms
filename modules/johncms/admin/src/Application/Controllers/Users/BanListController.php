<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\Services\BanListControlsMapper;
use Johncms\Modules\Admin\Application\Services\BanListFilterMapper;
use Johncms\Modules\Admin\Application\Services\BanListRowMapper;
use Johncms\Modules\Admin\Application\UseCases\GetBanListUseCase;
use Johncms\NavChain;

/**
 * The ban panel: the accounts under a ban now, or everybody who has had one.
 *
 * A live list, as the list of users: the script asks for the same address with
 * X-Requested-With and gets the regions of the page alone.
 */
final readonly class BanListController
{
    public function __construct(
        private NavChain $navChain,
        private AccessCheckerInterface $accessChecker,
        private GetBanListUseCase $getBanList,
        private BanListFilterMapper $filterMapper,
        private BanListControlsMapper $controlsMapper,
        private BanListRowMapper $rowMapper,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private Session $session,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $filter = $this->filterMapper->fromQuery($request->query->all());
        $pagination = $this->paginationFactory->create($this->getBanList->count($filter));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $entries = $this->getBanList->getPage($filter, $pagination->getPerPage(), $pagination->getOffset());

        $data = [
            'bans'       => $this->rowMapper->map($entries, $pagination->getUrl($pagination->getCurrentPage())),
            'controls'   => $this->controlsMapper->map($filter, $this->getBanList->tabCounts()),
            'search'     => $filter->search,
            'total'      => $pagination->getTotal(),
            'pagination' => $pagination->render(),
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@admin/bans/live.twig', $data);
        }

        $title = __('Ban Panel');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $pagination->getCurrentPage());

        return new ViewResponse('@admin/bans.twig', $data + [
            'title'           => $meta->title,
            'page_title'      => $title,
            'usr_menu'        => ['ban_panel' => true],
            'amnesty_url'     => $this->accessChecker->allows(CorePermissions::ADMIN_SETTINGS_MANAGE) ? '/admin/bans/amnesty' : '',
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }
}
