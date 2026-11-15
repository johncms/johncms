<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\GetSubmitVoteContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\SubmitVoteUseCase;
use Johncms\Http\Request;
use Johncms\Http\Session;

final readonly class SubmitVoteController
{
    public function __construct(
        private CurrentUser $currentUser,
        private Session $session,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetSubmitVoteContextUseCase $contextUseCase,
        private SubmitVoteUseCase $submitVoteUseCase,
        private ForumTopicPathService $topicPathService,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        try {
            $voteId = $request->bodyInt('vote', 0);
            $context = $this->contextUseCase->execute($id, $voteId, $this->currentUser->id());
        } catch (ForumAccessDeniedException | ForumValidationException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $this->submitVoteUseCase->execute($context->topicId, $context->voteId, $this->currentUser->id());

        $this->session->flash(ForumTopicController::MESSAGE, __('Vote accepted'));
        redirect($this->topicPathService->getTopicUrlById($context->topicId) ?? '/forum/');
    }
}
