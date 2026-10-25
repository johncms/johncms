<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Access;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\Services\AuthLogControlsMapper;
use Johncms\Modules\Admin\Application\Services\AuthLogFilterMapper;
use Johncms\Modules\Admin\Application\Services\AuthLogRowMapper;
use Johncms\Modules\Admin\Application\UseCases\GetAuthLogUseCase;
use Johncms\NavChain;

/**
 * The sign-in log: who signed in, what was refused, who changed whose roles.
 *
 * The trail is the point of recording anything at all — an entry nobody can read answers no
 * question — so the screen arrives together with the events it shows.
 *
 * A live list, as the list of users: the script asks for the same address with
 * X-Requested-With and gets the regions of the page alone.
 */
final readonly class AuthLogController
{
    public function __construct(
        private NavChain $navChain,
        private GetAuthLogUseCase $getAuthLog,
        private AuthLogFilterMapper $filterMapper,
        private AuthLogControlsMapper $controlsMapper,
        private AuthLogRowMapper $rowMapper,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $filter = $this->filterMapper->fromQuery($request->query->all());
        $pagination = $this->paginationFactory->create($this->getAuthLog->count($filter));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $userName = $filter->userId === null ? null : $this->getAuthLog->userName($filter->userId);

        $data = [
            'entries'    => $this->rowMapper->map($this->getAuthLog->getPage($filter, $pagination->getPerPage(), $pagination->getOffset())),
            'controls'   => $this->controlsMapper->map($filter, $userName),
            'search'     => $filter->search,
            'total'      => $pagination->getTotal(),
            'pagination' => $pagination->render(),
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@admin/auth-log/live.twig', $data);
        }

        $title = __('Sign-in log');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $pagination->getCurrentPage());

        return new ViewResponse('@admin/auth-log.twig', $data + [
            'title'      => $meta->title,
            'page_title' => $title,
            'usr_menu'   => ['auth_log' => true],
        ]);
    }
}
