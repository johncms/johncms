<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
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
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class ViewTopicVisitorsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private EnsureForumUserAccessUseCase $forumUserAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewTopicVisitorsUseCase $viewTopicVisitorsUseCase,
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumTopicPathService $topicPathService,
        private PaginationFactory $paginationFactory,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): Response
    {
        try {
            $this->forumUserAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
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

        $showGuests = $this->request->queryParam('mode') === 'guests';
        $page = max(1, $this->request->queryInt('page', 1));
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
            return $this->forumErrorRenderer->render(
                $this->render,
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

        return new Response(
            $this->render->render(
                'forum::who',
                [
                    'title'           => $caption,
                    'page_title'      => $caption,
                    'empty_message'   => __('The list is empty'),
                    'items'           => $result->items,
                    'pagination'      => $pagination->render(),
                    'total'           => $result->total,
                    'topic'           => $topic !== null ? htmlentities((string) $topic->name, ENT_QUOTES, 'UTF-8') : '',
                    'is_users'        => ! $showGuests,
                    'users_list_url'  => '/forum/topic-visitors/' . $id . '/',
                    'guests_list_url' => '/forum/topic-visitors/' . $id . '/?mode=guests',
                    'show_period'     => false,
                    'id'              => $id,
                    'topic_url'       => $this->topicPathService->getTopicUrlById($id) ?? '/forum/',
                ]
            )
        );
    }
}
