<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\DeleteVoteWrongDataException;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\UseCases\DeleteVoteUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureDeleteVoteAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class DeleteVoteController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumAccessResponseBuilder $forumAccessResponseBuilder,
        private EnsureDeleteVoteAccessUseCase $accessUseCase,
        private DeleteVoteUseCase $deleteVoteUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): string
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->render->render(
                'system::pages/result',
                $this->forumAccessResponseBuilder->forException($exception)
            );
        }

        try {
            $this->accessUseCase->execute($id);
        } catch (AccessDeniedException) {
            http_response_code(403);
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Access forbidden'),
                    'type'          => 'alert-danger',
                    'message'       => __('Access forbidden'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (DeleteVoteWrongDataException) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Delete Poll'),
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        if ($this->request->getQuery('yes') !== null) {
            $this->deleteVoteUseCase->execute($id);
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Delete Poll'),
                    'type'          => 'alert-success',
                    'message'       => __('Poll deleted'),
                    'back_url'      => '/forum/?type=topic&id=' . $id,
                    'back_url_name' => __('Back'),
                ]
            );
        }

        return $this->render->render(
            'forum::delete_poll',
            [
                'title'      => __('Delete Poll'),
                'page_title' => __('Delete Poll'),
                'id'         => $id,
                'back_url'   => '/forum/?type=topic&id=' . $id,
                'delete_url' => '/forum/delvote/' . $id . '/?yes',
            ]
        );
    }
}
