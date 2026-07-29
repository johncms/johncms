<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\DeleteVoteUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureDeleteVoteAccessUseCase;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteVoteController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private ForumErrorRenderer $forumErrorRenderer,
        private EnsureDeleteVoteAccessUseCase $accessUseCase,
        private DeleteVoteUseCase $deleteVoteUseCase,
        private ForumTopicPathService $topicPathService,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(Request $request, int $id): Response
    {
        try {
            $this->accessUseCase->execute($id);
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (ForumValidationException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'title'         => __('Delete Poll'),
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        if ($request->query->has('yes')) {
            $this->deleteVoteUseCase->execute($id);
            return new Response(
                $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Delete Poll'),
                        'type'          => 'alert-success',
                        'message'       => __('Poll deleted'),
                        'back_url'      => $this->topicPathService->getTopicUrlById($id) ?? '/forum/',
                        'back_url_name' => __('Back'),
                    ]
                )
            );
        }

        return new Response(
            $this->render->render(
                'forum::delete_poll',
                [
                    'title'      => __('Delete Poll'),
                    'page_title' => __('Delete Poll'),
                    'id'         => $id,
                    'back_url'   => $this->topicPathService->getTopicUrlById($id) ?? '/forum/',
                    'delete_url' => '/forum/delvote/' . $id . '/?yes',
                ]
            )
        );
    }
}
