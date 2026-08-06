<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\GetSubmitVoteContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\SubmitVoteUseCase;
use Johncms\Http\Request;
use Johncms\Users\User;

final readonly class SubmitVoteController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private User $user,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetSubmitVoteContextUseCase $contextUseCase,
        private SubmitVoteUseCase $submitVoteUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        try {
            $voteId = $request->bodyInt('vote', 0);
            $context = $this->contextUseCase->execute($id, $voteId, $this->user->id);
        } catch (ForumAccessDeniedException | ForumValidationException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $this->submitVoteUseCase->execute($context->topicId, $context->voteId, $this->user->id);

        $referer = htmlspecialchars((string) $request->server->getString('HTTP_REFERER', '/forum/'));
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'         => __('Forum'),
                'page_title'    => __('Forum'),
                'type'          => 'alert-success',
                'message'       => __('Vote accepted'),
                'back_url'      => $referer,
                'back_url_name' => __('Back'),
            ]
        );
    }
}
