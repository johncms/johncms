<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
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
        private ForumErrorRenderer $forumErrorRenderer,
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
            return $this->forumErrorRenderer->render($this->render, $exception);
        }

        try {
            $this->accessUseCase->execute();
            $context = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (ForumNotFoundException) {
            pageNotFound();
        }

        $closed = $this->request->getQuery('closed') !== null;
        $this->closeTopicUseCase->execute($context->topicId, $closed);
        redirect($this->topicPathService->getTopicUrlById($context->topicId) ?? '/forum/');
    }
}
