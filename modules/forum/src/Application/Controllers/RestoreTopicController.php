<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\RestoreTopicNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureRestoreTopicAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetRestoreTopicContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\RestoreTopicUseCase;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class RestoreTopicController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private User $user,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumAccessResponseBuilder $forumAccessResponseBuilder,
        private EnsureRestoreTopicAccessUseCase $accessUseCase,
        private GetRestoreTopicContextUseCase $contextUseCase,
        private RestoreTopicUseCase $restoreTopicUseCase,
        private ForumTopicPathService $topicPathService,
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
            $this->accessUseCase->execute();
            $topicId = $this->contextUseCase->execute($id);
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
        } catch (RestoreTopicNotFoundException) {
            pageNotFound();
        }

        $this->restoreTopicUseCase->execute($topicId, $this->user->name);
        redirect($this->topicPathService->getTopicUrlById($topicId) ?? '/forum/');
    }
}
