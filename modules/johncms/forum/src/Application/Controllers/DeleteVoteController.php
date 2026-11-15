<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\DeleteVoteUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureDeleteVoteAccessUseCase;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;

final readonly class DeleteVoteController
{
    public function __construct(
        private Session $session,
        private ForumErrorRenderer $forumErrorRenderer,
        private EnsureDeleteVoteAccessUseCase $accessUseCase,
        private DeleteVoteUseCase $deleteVoteUseCase,
        private ForumTopicPathService $topicPathService,
    ) {
    }

    public function __invoke(int $id): ViewResponse
    {
        try {
            $this->accessUseCase->execute($id);
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (ForumValidationException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'title'         => __('Delete Poll'),
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $this->deleteVoteUseCase->execute($id);
        $this->session->flash(ForumTopicController::MESSAGE, __('Poll deleted'));
        redirect($this->topicPathService->getTopicUrlById($id) ?? '/forum/');
    }
}
