<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsurePinTopicAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetPinTopicContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\PinTopicUseCase;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class PinTopicController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private EnsurePinTopicAccessUseCase $accessUseCase,
        private GetPinTopicContextUseCase $contextUseCase,
        private PinTopicUseCase $pinTopicUseCase,
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
            $topicId = $this->contextUseCase->execute($id);
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

        $pin = $this->request->getQuery('pin') !== null;
        $this->pinTopicUseCase->execute($topicId, $pin);
        redirect($this->topicPathService->getTopicUrlById($topicId) ?? '/forum/');
    }
}
