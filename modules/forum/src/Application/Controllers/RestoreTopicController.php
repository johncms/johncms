<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\GetRestoreTopicContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\RestoreTopicUseCase;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class RestoreTopicController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private User $user,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetRestoreTopicContextUseCase $contextUseCase,
        private RestoreTopicUseCase $restoreTopicUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): Response
    {
        try {
            $topic = $this->contextUseCase->execute($id);
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

        $this->restoreTopicUseCase->execute($topic->id, $this->user->name);
        redirect($topic->url);
    }
}
