<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\DTO\ForumSearchQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumSearchInvalidLengthException;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
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
        private ForumAccessResponseBuilder $forumAccessResponseBuilder,
        private ViewForumSearchUseCase $viewForumSearchUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(): string
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->render->render(
                'system::pages/result',
                $this->forumAccessResponseBuilder->forException($exception)
            );
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
        } catch (ForumSearchInvalidLengthException) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Forum search'),
                    'type'          => 'alert-danger',
                    'message'       => __('Invalid length'),
                    'back_url'      => '/forum/search/',
                    'back_url_name' => __('Repeat'),
                ]
            );
        }

        $this->navChain->add(__('Forum'), '/forum/');
        $this->navChain->add(__('Forum search'));

        return $this->render->render(
            'forum::forum_search',
            [
                'title'             => __('Forum search'),
                'page_title'        => __('Forum search'),
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
}
