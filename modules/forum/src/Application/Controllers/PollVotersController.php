<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\DTO\PollVotersQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\PollVotersWrongDataException;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsurePollVotersAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\ViewPollVotersUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class PollVotersController
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
        private EnsurePollVotersAccessUseCase $accessUseCase,
        private ViewPollVotersUseCase $viewPollVotersUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): string
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->render->render(
                'system::pages/result',
                $this->forumAccessResponseBuilder->forException($exception)
            );
        }

        try {
            $this->accessUseCase->execute($id);
            $result = $this->viewPollVotersUseCase->execute(
                new PollVotersQueryDTO(
                    topicId: $id,
                    start: max(0, (int) $this->request->getQuery('start', 0)),
                )
            );
        } catch (AccessDeniedException) {
            http_response_code(403);

            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Access forbidden'),
                    'type'          => 'alert-danger',
                    'message'       => __('Access forbidden'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (PollVotersWrongDataException) {
            http_response_code(404);

            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Who voted in the poll'),
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Forum'),
                ]
            );
        }

        $caption = __('Who voted in the poll');
        $this->navChain->add(__('Forum'), '/forum/');
        $this->navChain->add($caption);

        return $this->render->render(
            'forum::voted_users',
            [
                'title'         => $caption,
                'page_title'    => $caption,
                'empty_message' => __('No one has voted in this poll yet'),
                'poll_name'     => htmlentities($result->pollName, ENT_QUOTES, 'UTF-8'),
                'items'         => $result->items,
                'pagination'    => $this->tools->displayPagination(
                    '/forum/poll-voters/' . $id . '/?',
                    max(0, (int) $this->request->getQuery('start', 0)),
                    $result->total,
                    $this->currentUser->config->kmess
                ),
                'total'         => $result->total,
                'id'            => $id,
            ]
        );
    }
}
