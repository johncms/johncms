<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Forum\Application\DTO\ForumSearchQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\UseCases\ViewForumSearchUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class ForumSearchController
{
    public function __construct(
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private ViewForumSearchUseCase $viewForumSearchUseCase,
        private PaginationFactory $paginationFactory,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $search = rawurldecode(trim($request->queryParam('search', '')));
        $searchInTopicNames = $request->query->has('t');
        $page = max(1, $request->queryInt('page', 1));
        $offset = ($page - 1) * (int) $this->currentUser->user()->config->kmess;

        $this->navChain->add(__('Forum'), '/forum/');
        $this->navChain->add(__('Forum search'));

        try {
            $result = $this->viewForumSearchUseCase->execute(
                new ForumSearchQueryDTO(
                    start: $offset,
                    search: $search,
                    searchInTopicNames: $searchInTopicNames,
                )
            );
        } catch (ForumValidationException) {
            // The query comes back in its field with the reason under it.
            return new ViewResponse(
                '@forum/public/search.twig',
                [
                    'title'             => __('Forum search'),
                    'page_title'        => __('Forum search'),
                    'query'             => $search,
                    'search_t'          => $searchInTopicNames,
                    'searched'          => false,
                    'error'             => __('The query takes from 4 to 64 characters.'),
                    'results'           => [],
                    'total'             => 0,
                    'pagination'        => null,
                    'search_history'    => [],
                    'history_reset_url' => '/forum/search/history/clear/',
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $searchTitle = $result->query !== ''
            ? __('Search results for: %s', $result->query)
            : __('Forum search');

        $pagination = $this->paginationFactory->create($result->total, null, 'page', $page);

        return new ViewResponse(
            '@forum/public/search.twig',
            [
                'title'             => $this->buildSearchDocumentTitle($searchTitle, $page),
                'page_title'        => $searchTitle,
                'description'       => $this->buildSearchDescription($searchTitle, $page),
                'pagination'        => $pagination->hasPages() ? $pagination->render(withRange: true) : null,
                'query'             => $result->query,
                'search_t'          => $result->searchInTopicNames,
                'searched'          => $result->query !== '',
                'error'             => '',
                'results'           => $result->results,
                'total'             => $result->total,
                'search_history'    => $result->historyTerms,
                'history_reset_url' => '/forum/search/history/clear/',
            ]
        );
    }

    private function buildSearchDocumentTitle(string $baseTitle, int $page): string
    {
        if ($page <= 1) {
            return $baseTitle;
        }

        return $baseTitle . ' — ' . d__('system', 'Page') . ' ' . $page;
    }

    private function buildSearchDescription(string $baseDescription, int $page): string
    {
        if ($page <= 1) {
            return $baseDescription;
        }

        return $baseDescription . ' — ' . d__('system', 'Page') . ' ' . $page;
    }
}
