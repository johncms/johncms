<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\Services\UserListControlsMapper;
use Johncms\Modules\Admin\Application\Services\UserListFilterMapper;
use Johncms\Modules\Admin\Application\Services\UserListRowMapper;
use Johncms\Modules\Admin\Application\UseCases\GetUserListUseCase;
use Johncms\NavChain;

/**
 * Every account of the site: searched, filtered and ordered by the query string.
 *
 * The page is a live list: as a search is typed or a filter is picked, its script asks the same
 * address again and puts the parts that changed in place (admin::users/live). Without the script
 * the same links and the same form work as a plain page.
 */
final readonly class UserListController
{
    public function __construct(
        private NavChain $navChain,
        private GetUserListUseCase $getUserList,
        private UserListFilterMapper $filterMapper,
        private UserListControlsMapper $controlsMapper,
        private UserListRowMapper $rowMapper,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private Session $session,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $filter = $this->filterMapper->fromQuery($request->query->all());
        $pagination = $this->paginationFactory->create($this->getUserList->count($filter));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $stats = $this->getUserList->stats();
        $page = $this->getUserList->getPage($filter, $pagination->getPerPage(), $pagination->getOffset());

        $data = [
            'users'      => $this->rowMapper->mapPage($page, $pagination->getUrl($pagination->getCurrentPage())),
            'controls'   => $this->controlsMapper->map($filter, $stats, $this->getUserList->grantableRoles()),
            'search'     => $filter->search,
            'pending'    => $stats->pending,
            'total'      => $pagination->getTotal(),
            'show_ip'    => $this->rowMapper->showsOrigin(),
            'pagination' => $pagination->render(),
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@admin/users/live.twig', $data);
        }

        $title = __('Users');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $pagination->getCurrentPage());

        return new ViewResponse(
            '@admin/users.twig',
            $data + [
                'title'           => $meta->title,
                'page_title'      => $title,
                'usr_menu'        => ['userlist' => true],
                'stats'           => $stats,
                'stat_urls'       => $this->controlsMapper->statUrls(),
                'success_message' => (string) $this->session->getFlash('success_message'),
            ]
        );
    }
}
