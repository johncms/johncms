<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\GetRestoreTopicContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\RestoreTopicUseCase;
use Johncms\Users\User;

final readonly class RestoreTopicController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private User $user,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetRestoreTopicContextUseCase $contextUseCase,
        private RestoreTopicUseCase $restoreTopicUseCase,
    ) {
        $this->controllerContext->initModule('forum');
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

        $this->restoreTopicUseCase->execute($topic->id, $this->user->name);
        redirect($topic->url);
    }
}
