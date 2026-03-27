<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\DTO\ForumSearchQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\ViewForumSearchUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class ForumSearchController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewForumSearchUseCase $viewForumSearchUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(): string
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render($this->render, $exception);
        }

        $search = rawurldecode(trim((string) $this->request->getQuery('search', '')));
        $searchInTopicNames = $this->request->getQuery('t') !== null;
        $page = max(0, (int) $this->request->getQuery('page', 0));
        $start = $page > 0
            ? ($page - 1) * (int) $this->currentUser->config->kmess
            : max(0, (int) $this->request->getQuery('start', 0));

        try {
            $result = $this->viewForumSearchUseCase->execute(
                new ForumSearchQueryDTO(
                    start: $start,
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

        $currentPage = $this->resolveCurrentPage($result->start, (int) $this->currentUser->config->kmess);
        $searchTitle = $result->query !== ''
            ? __('Search results for: %s', $result->query)
            : __('Forum search');

        $this->render->addData(
            [
                'title'       => $this->buildSearchDocumentTitle($searchTitle, $currentPage),
                'page_title'  => $searchTitle,
                'description' => $this->buildSearchDescription($searchTitle, $currentPage),
            ]
        );

        return $this->render->render(
            'forum::forum_search',
            [
                'pagination'        => $this->tools->displayPagination(
                    '/forum/search/?' . ($result->searchInTopicNames ? 't=1&amp;' : '') . 'search=' . urlencode($result->query) . '&amp;',
                    $result->start,
                    $result->total,
                    $this->currentUser->config->kmess
                ),
                'query'             => $this->tools->checkout($result->query, 0, 0),
                'search_t'          => $result->searchInTopicNames,
                'results'           => $result->results,
                'total'             => $result->total,
                'search_history'    => $result->historyTerms,
                'history_reset_url' => '/forum/search/history/clear/',
            ]
        );
    }

    private function resolveCurrentPage(int $start, int $perPage): int
    {
        $safePerPage = max(1, $perPage);

        return (int) floor(max(0, $start) / $safePerPage) + 1;
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
