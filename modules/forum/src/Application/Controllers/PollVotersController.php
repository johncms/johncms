<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Forum\Application\DTO\PollVotersQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\EnsurePollVotersAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\ViewPollVotersUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\Response;

final readonly class PollVotersController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private ForumErrorRenderer $forumErrorRenderer,
        private EnsurePollVotersAccessUseCase $accessUseCase,
        private ViewPollVotersUseCase $viewPollVotersUseCase,
        private ForumTopicPathService $topicPathService,
        private PaginationFactory $paginationFactory,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): Response
    {
        try {
            $this->accessUseCase->execute($id);
            $page = max(1, $this->request->queryInt('page', 1));
            $result = $this->viewPollVotersUseCase->execute(
                new PollVotersQueryDTO(
                    topicId: $id,
                    page: $page,
                )
            );
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (ForumValidationException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'title'         => __('Who voted in the poll'),
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Forum'),
                ]
            );
        }

        $caption = __('Who voted in the poll');
        $this->navChain->add(__('Forum'), '/forum/');
        $this->navChain->add($caption);

        $pagination = $this->paginationFactory->create($result->total, null, 'page', $page);

        return new Response(
            $this->render->render(
                'forum::voted_users',
                [
                    'title'         => $caption,
                    'page_title'    => $caption,
                    'empty_message' => __('No one has voted in this poll yet'),
                    'poll_name'     => htmlentities($result->pollName, ENT_QUOTES, 'UTF-8'),
                    'items'         => $result->items,
                    'pagination'    => $pagination->render(),
                    'total'         => $result->total,
                    'id'            => $id,
                    'topic_url'     => $this->topicPathService->getTopicUrlById($id) ?? '/forum/',
                ]
            )
        );
    }
}
