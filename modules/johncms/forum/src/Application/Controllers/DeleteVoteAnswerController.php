<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\DeleteVoteAnswerUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetEditVoteContextUseCase;

/**
 * Removes one answer of the poll of a topic, asked for in the dialog of the editing form, and
 * goes back to that form. A poll keeps at least two answers: the last two are not removed.
 */
final readonly class DeleteVoteAnswerController
{
    public function __construct(
        private ForumErrorRenderer $forumErrorRenderer,
        private GetEditVoteContextUseCase $contextUseCase,
        private DeleteVoteAnswerUseCase $deleteVoteAnswerUseCase,
    ) {
    }

    public function __invoke(int $id, int $answer): ViewResponse
    {
        try {
            $context = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (ForumValidationException | ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'title'         => __('Delete Answer'),
                    'page_title'    => __('Delete Answer'),
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $this->deleteVoteAnswerUseCase->execute($context->topicId, $answer);
        redirect('/forum/editvote/' . $context->topicId . '/');
    }
}
