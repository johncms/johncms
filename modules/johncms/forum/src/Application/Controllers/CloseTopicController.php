<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\CloseTopicUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetCloseTopicContextUseCase;
use Johncms\Http\Request;

final readonly class CloseTopicController
{
    public function __construct(
        private ForumErrorRenderer $forumErrorRenderer,
        private GetCloseTopicContextUseCase $contextUseCase,
        private CloseTopicUseCase $closeTopicUseCase,
        private ForumTopicPathService $topicPathService,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        try {
            $topicId = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse(
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
