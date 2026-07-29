<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Forum\Application\DTO\ForumSearchQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\ViewForumSearchUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class ForumSearchController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewForumSearchUseCase $viewForumSearchUseCase,
        private PaginationFactory $paginationFactory,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(Request $request): Response
    {
        $search = rawurldecode(trim($request->queryParam('search', '')));
        $searchInTopicNames = $request->query->has('t');
        $page = max(1, $request->queryInt('page', 1));
        $offset = ($page - 1) * (int) $this->currentUser->config->kmess;

        try {
            $result = $this->viewForumSearchUseCase->execute(
                new ForumSearchQueryDTO(
                    start: $offset,
                    search: $search,
                    searchInTopicNames: $searchInTopicNames,
                )
            );
        } catch (ForumValidationException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'title'         => __('Forum search'),
                    'message'       => __('Invalid length'),
                    'back_url'      => '/forum/search/',
                    'back_url_name' => __('Repeat'),
                ]
            );
        }

        $this->navChain->add(__('Forum'), '/forum/');
        $this->navChain->add(__('Forum search'));

        $searchTitle = $result->query !== ''
            ? __('Search results for: %s', $result->query)
            : __('Forum search');

        $this->render->addData(
            [
                'title'       => $this->buildSearchDocumentTitle($searchTitle, $page),
                'page_title'  => $searchTitle,
                'description' => $this->buildSearchDescription($searchTitle, $page),
            ]
        );

        $pagination = $this->paginationFactory->create($result->total, null, 'page', $page);

        return new Response(
            $this->render->render(
                'forum::forum_search',
                [
                    'pagination'        => $pagination->render(),
                    'query'             => $result->query,
                    'search_t'          => $result->searchInTopicNames,
                    'results'           => $result->results,
                    'total'             => $result->total,
                    'search_history'    => $result->historyTerms,
                    'history_reset_url' => '/forum/search/history/clear/',
                ]
            )
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
