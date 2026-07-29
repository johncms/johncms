<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\CloseTopicUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetCloseTopicContextUseCase;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\Response;

final readonly class CloseTopicController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetCloseTopicContextUseCase $contextUseCase,
        private CloseTopicUseCase $closeTopicUseCase,
        private ForumTopicPathService $topicPathService,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(Request $request, int $id): Response
    {
        try {
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

        $closed = $request->query->has('closed');
        $this->closeTopicUseCase->execute($topicId, $closed);
        redirect($this->topicPathService->getTopicUrlById($topicId) ?? '/forum/');
    }
}
