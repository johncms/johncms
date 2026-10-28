<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers\Admin;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Services\HiddenListControlsMapper;
use Johncms\Modules\Forum\Application\Services\HiddenListFilterMapper;
use Johncms\Modules\Forum\Application\Services\HiddenListRowMapper;
use Johncms\Modules\Forum\Application\UseCases\ManageHiddenForumUseCase;
use Johncms\Modules\Forum\Domain\Enums\HiddenListTab;
use Johncms\NavChain;

/**
 * The hidden posts and topics of the forum, a tab each, searched and narrowed from the query
 * string.
 *
 * A live list, as the list of users: the script asks for the same address with X-Requested-With
 * and gets the regions of the page alone.
 */
final readonly class HiddenListController
{
    public function __construct(
        private NavChain $navChain,
        private AccessCheckerInterface $accessChecker,
        private ManageHiddenForumUseCase $hidden,
        private HiddenListFilterMapper $filterMapper,
        private HiddenListControlsMapper $controlsMapper,
        private HiddenListRowMapper $rowMapper,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private Session $session,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $filter = $this->filterMapper->fromQuery($request->query->all());
        $pagination = $this->paginationFactory->create($this->hidden->count($filter));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $posts = $filter->tab === HiddenListTab::POSTS;
        $total = $pagination->getTotal();

        $data = [
            'tab'        => $filter->tab->value,
            'rows'       => $posts
                ? $this->rowMapper->posts($this->hidden->posts($filter, $pagination->getPerPage(), $pagination->getOffset()), $filter)
                : $this->rowMapper->topics($this->hidden->topics($filter, $pagination->getPerPage(), $pagination->getOffset()), $filter),
            'controls'   => $this->controlsMapper->map($filter, $this->hidden->totals(), $this->hidden->hiders($filter->tab), $this->hidden->names($filter)),
            'search'     => $filter->search,
            'total'      => $total,
            'pagination' => $pagination->render(),
            // Emptying the list deletes what the filter lets through, so the dialog gets the same
            // filter.
            'purge_url'  => $total > 0 && $this->accessChecker->allows(ForumPermissions::HIDDEN_PURGE)
                ? $this->filterMapper->url($filter, HiddenListFilterMapper::PURGE_PATH)
                : '',
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@forum/admin/hidden-live.twig', $data);
        }

        $title = __('Hidden');
        $this->navChain->add(__('Forum'), '/admin/forum');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $pagination->getCurrentPage());

        return new ViewResponse('@forum/admin/hidden.twig', $data + [
            'title'           => $meta->title,
            'page_title'      => $title,
            'module_menu'     => ['forum' => true],
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }
}
