<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\GetRestoreTopicContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\RestoreTopicUseCase;

final readonly class RestoreTopicController
{
    public function __construct(
        private CurrentUser $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetRestoreTopicContextUseCase $contextUseCase,
        private RestoreTopicUseCase $restoreTopicUseCase,
    ) {
    }

    public function __invoke(int $id): ViewResponse
    {
        try {
            $topic = $this->contextUseCase->execute($id);
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

        $this->restoreTopicUseCase->execute($topic->id, $this->currentUser->user()->name);
        redirect($topic->url);
    }
}
