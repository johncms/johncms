<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Ip;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Admin\Application\Services\AdminUserRowMapper;
use Johncms\Modules\Admin\Application\UseCases\SearchUsersByIpUseCase;
use Johncms\Modules\Admin\Domain\Enums\IpSearchMode;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class IpSearchController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private SearchUsersByIpUseCase $searchUsersByIpUseCase,
        private AdminUserRowMapper $rowMapper,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function __invoke(?string $mode = null): string
    {
        $searchMode = $mode === 'history' ? IpSearchMode::HISTORY : IpSearchMode::ACTUAL;
        $baseUrl = $searchMode === IpSearchMode::HISTORY ? '/admin/ip-search/history' : '/admin/ip-search';

        $search = (string) $this->request->getQuery('ip', '', FILTER_VALIDATE_IP);
        if ($search === '') {
            $search = trim((string) $this->request->getQuery('search', ''));
        }

        $page = max(1, (int) $this->request->getQuery('page', 1));
        $perPage = $this->currentUser->config->kmess;

        $result = $this->searchUsersByIpUseCase->execute($search, $searchMode, $page, $perPage);
        $total = $result->total();

        $title = __('Search IP');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $page);
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
                'items'    => $result->users !== null ? $this->rowMapper->mapMany($result->users->getCollection()) : [],
                'errors'   => $result->errors,
                'total'    => $total,
                'per_page' => $perPage,
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
                'pagination' => $this->tools->displayPagination(
                    $baseUrl . '?search=' . $encodedSearch . '&',
                    ($page - 1) * $perPage,
                    $total,
                    $perPage
                ),
            ]
        );
    }
}
