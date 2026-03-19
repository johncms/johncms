<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\CloseTopicNotFoundException;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\CloseTopicUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureCloseTopicAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetCloseTopicContextUseCase;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class CloseTopicController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumAccessResponseBuilder $forumAccessResponseBuilder,
        private EnsureCloseTopicAccessUseCase $accessUseCase,
        private GetCloseTopicContextUseCase $contextUseCase,
        private CloseTopicUseCase $closeTopicUseCase,
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
            $context = $this->contextUseCase->execute($id);
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
        } catch (CloseTopicNotFoundException) {
            pageNotFound();
        }

        $closed = $this->request->getQuery('closed') !== null;
        $this->closeTopicUseCase->execute($context->topicId, $closed);
        redirect($this->topicPathService->getTopicUrlById($context->topicId) ?? '/forum/');
    }
}
