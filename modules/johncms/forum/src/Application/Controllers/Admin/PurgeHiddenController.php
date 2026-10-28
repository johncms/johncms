<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers\Admin;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Services\HiddenListControlsMapper;
use Johncms\Modules\Forum\Application\Services\HiddenListFilterMapper;
use Johncms\Modules\Forum\Application\UseCases\ManageHiddenForumUseCase;
use Johncms\Modules\Forum\Domain\Enums\HiddenListTab;
use Johncms\Modules\Forum\Domain\ValueObjects\HiddenListFilter;
use Johncms\NavChain;

/**
 * Emptying the list of hidden posts or topics for good: what the filter of the list lets through,
 * nothing else. The confirmation is a dialog that says how much goes; the modal of the panel asks
 * for it with X-Requested-With, a plain visit gets it as a page.
 *
 * The filter travels in the query string both ways, so the form posts back to the address the
 * dialog was opened at.
 */
final readonly class PurgeHiddenController
{
    public function __construct(
        private NavChain $navChain,
        private ManageHiddenForumUseCase $hidden,
        private HiddenListFilterMapper $filterMapper,
        private HiddenListControlsMapper $controlsMapper,
        private Session $session,
    ) {
    }

    public function index(Request $request): ViewResponse
    {
        $filter = $this->filterMapper->fromQuery($request->query->all());
        $listUrl = $this->filterMapper->url($filter);

        $data = [
            'tab'         => $filter->tab->value,
            'count'       => $this->hidden->count($filter),
            'chips'       => $this->controlsMapper->chips($filter, $this->hidden->names($filter)),
            'form_action' => $this->filterMapper->url($filter, HiddenListFilterMapper::PURGE_PATH),
            'back_url'    => $listUrl,
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@forum/admin/purge-hidden-dialog.twig', $data);
        }

        $title = $filter->tab === HiddenListTab::POSTS ? __('Delete the hidden posts') : __('Delete the hidden topics');
        $this->navChain->add(__('Forum'), '/admin/forum');
        $this->navChain->add(__('Hidden'), $listUrl);
        $this->navChain->add($title);

        return new ViewResponse('@forum/admin/purge-hidden.twig', [
            // The dialog carries the heading itself.
            'title'       => $title,
            'module_menu' => ['forum' => true],
            'dialog'      => $data,
        ]);
    }

    public function purge(Request $request): ViewResponse
    {
        $filter = $this->filterMapper->fromQuery($request->query->all());
        $this->hidden->purge($filter);

        $this->session->flash('success_message', $filter->tab === HiddenListTab::POSTS ? __('The hidden posts were deleted') : __('The hidden topics were deleted'));
        // The filter let through what is gone now; the tab is all that is left of it.
        redirect($this->filterMapper->url(new HiddenListFilter($filter->tab)));
    }
}
