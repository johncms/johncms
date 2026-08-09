<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Modules\Forum\Application\DTO\ForumVisitorsQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumUserAccessUseCase;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Forum\Application\UseCases\ViewTopicVisitorsUseCase;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;

final readonly class ViewTopicVisitorsController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private EnsureForumUserAccessUseCase $forumUserAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewTopicVisitorsUseCase $viewTopicVisitorsUseCase,
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumTopicPathService $topicPathService,
        private PaginationFactory $paginationFactory,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        try {
            $this->forumUserAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'title'         => __('Who in Topic'),
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Forum'),
                ]
            );
        }

        $showGuests = $request->queryParam('mode') === 'guests';
        $page = max(1, $request->queryInt('page', 1));
        $start = ($page - 1) * (int) $this->currentUser->config->kmess;

        try {
            $result = $this->viewTopicVisitorsUseCase->execute(
                $id,
                new ForumVisitorsQueryDTO(
                    start: $start,
                    guests: $showGuests,
                )
            );
        } catch (ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'title'         => __('Who in Topic'),
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Forum'),
                ]
            );
        }

        $topic = $this->topicRepository->findById($id);
        $caption = __('Who in Topic');
        $this->navChain->add(__('Forum'), '/forum/');
        $this->navChain->add($caption);

        $pagination = $this->paginationFactory->create($result->total, null, 'page', $page);

        return new ViewResponse(
            '@forum/public/visitors.twig',
            [
                'title'           => $caption,
                'page_title'      => $caption,
                'empty_message'   => __('The list is empty'),
                'items'           => $result->items,
                'pagination'      => $pagination->hasPages() ? $pagination->render() : null,
                'total'           => $result->total,
                'topic_name'      => (string) ($topic->name ?? ''),
                'is_users'        => ! $showGuests,
                'users_list_url'  => '/forum/topic-visitors/' . $id . '/',
                'guests_list_url' => '/forum/topic-visitors/' . $id . '/?mode=guests',
                'topic_url'       => $this->topicPathService->getTopicUrlById($id) ?? '/forum/',
            ]
        );
    }
}
