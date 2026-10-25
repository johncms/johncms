<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Ip;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\Services\IpSearchRowMapper;
use Johncms\Modules\Admin\Application\UseCases\SearchUsersByIpUseCase;
use Johncms\Modules\Admin\Domain\Enums\IpSearchMode;
use Johncms\NavChain;

/**
 * Looking accounts up by an address, a range or a mask: in the addresses they have now, or in the
 * history of the addresses they came from.
 *
 * The links of the site lead here with ?ip= and an exact address; the form sends ?search=. A live
 * list: the script asks for the same address with X-Requested-With and gets the regions alone.
 */
final readonly class IpSearchController
{
    public function __construct(
        private NavChain $navChain,
        private SearchUsersByIpUseCase $searchUsersByIp,
        private IpSearchRowMapper $rowMapper,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(Request $request, ?string $mode = null): ViewResponse
    {
        $searchMode = $mode === 'history' ? IpSearchMode::HISTORY : IpSearchMode::ACTUAL;

        $search = (string) (filter_var($request->queryParam('ip'), FILTER_VALIDATE_IP) ?: '');
        if ($search === '') {
            $search = trim($request->queryParam('search', ''));
        }

        $pagination = $this->paginationFactory->create($this->searchUsersByIp->count($search, $searchMode));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $result = $this->searchUsersByIp->getPage($search, $searchMode, $pagination->getPerPage(), $pagination->getOffset());

        $data = [
            'search'      => $search,
            'mode'        => $searchMode->value,
            'tabs'        => $this->tabs($search, $searchMode, $result->errors === [], $pagination->getTotal()),
            'users'       => $result->users === null
                ? []
                : $this->rowMapper->map($result->users, $searchMode, $pagination->getUrl($pagination->getCurrentPage())),
            'searched'    => $result->users !== null,
            'errors'      => $result->errors,
            'show_origin' => $this->rowMapper->showsOrigin(),
            'total'       => $pagination->getTotal(),
            'pagination'  => $pagination->render(),
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@admin/ip-search/live.twig', $data);
        }

        $title = __('Search IP');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $pagination->getCurrentPage());

        return new ViewResponse('@admin/ip-search.twig', $data + [
            'title'       => $meta->title,
            'page_title'  => $title,
            'usr_menu'    => ['search_ip' => true],
            'form_action' => $this->rowMapper->searchUrl('', $searchMode),
        ]);
    }

    /**
     * The two places to look in, each with how many accounts it holds once there is a search.
     *
     * @param int $currentTotal What the open tab holds, counted for its pages already.
     * @return list<array<string, mixed>>
     */
    private function tabs(string $search, IpSearchMode $current, bool $valid, int $currentTotal): array
    {
        $titles = [
            IpSearchMode::ACTUAL->value  => __('Actual IP'),
            IpSearchMode::HISTORY->value => __('IP history'),
        ];

        $tabs = [];
        foreach (IpSearchMode::cases() as $mode) {
            $tabs[] = [
                'title'  => $titles[$mode->value],
                'url'    => $this->rowMapper->searchUrl($search, $mode),
                'active' => $mode === $current,
                'count'  => match (true) {
                    $search === '' || ! $valid => 0,
                    $mode === $current         => $currentTotal,
                    default                    => $this->searchUsersByIp->count($search, $mode),
                },
                'tone'   => '',
            ];
        }

        return $tabs;
    }
}
