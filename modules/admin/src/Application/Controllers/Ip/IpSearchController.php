<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Ip;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Admin\Application\Services\AdminUserRowMapper;
use Johncms\Modules\Admin\Application\UseCases\SearchUsersByIpUseCase;
use Johncms\Modules\Admin\Domain\Enums\IpSearchMode;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class IpSearchController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private SearchUsersByIpUseCase $searchUsersByIpUseCase,
        private AdminUserRowMapper $rowMapper,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function __invoke(?string $mode = null): string
    {
        $searchMode = $mode === 'history' ? IpSearchMode::HISTORY : IpSearchMode::ACTUAL;

        $search = (string) $this->request->getQuery('ip', '', FILTER_VALIDATE_IP);
        if ($search === '') {
            $search = trim((string) $this->request->getQuery('search', ''));
        }

        $pagination = $this->paginationFactory->create($this->searchUsersByIpUseCase->count($search, $searchMode));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $result = $this->searchUsersByIpUseCase->getPage(
            $search,
            $searchMode,
            $pagination->getPerPage(),
            $pagination->getOffset()
        );

        $title = __('Search IP');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $pagination->getCurrentPage());
        $this->render->addData(
            [
                'title'      => $meta->title,
                'page_title' => $title,
                'usr_menu'   => ['search_ip' => true],
            ]
        );

        $encodedSearch = urlencode($search);

        return $this->render->render(
            'admin::search_ip',
            [
                'search'   => $search,
                'items'    => $result->users !== null ? $this->rowMapper->mapMany($result->users) : [],
                'errors'   => $result->errors,
                'total'    => $pagination->getTotal(),
                'per_page' => $pagination->getPerPage(),
                'filters'  => [
                    [
                        'url'    => '/admin/ip-search?search=' . $encodedSearch,
                        'name'   => __('Actual IP'),
                        'active' => $searchMode === IpSearchMode::ACTUAL,
                    ],
                    [
                        'url'    => '/admin/ip-search/history?search=' . $encodedSearch,
                        'name'   => __('IP history'),
                        'active' => $searchMode === IpSearchMode::HISTORY,
                    ],
                ],
                'pagination' => $pagination->render(),
            ]
        );
    }
}
