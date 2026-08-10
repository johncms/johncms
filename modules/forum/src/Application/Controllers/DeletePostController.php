<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\DeletePostUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureEditPostAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetEditPostContextUseCase;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;

final readonly class DeletePostController
{
    public function __construct(
        private User $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetEditPostContextUseCase $contextUseCase,
        private EnsureEditPostAccessUseCase $accessUseCase,
        private DeletePostUseCase $deletePostUseCase,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        try {
            $context = $this->contextUseCase->execute($id, $this->getForumSettings());
            $this->accessUseCase->execute($context);
        } catch (ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'title'         => __('Error'),
                    'message'       => __('Message does not exists or has been deleted'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Forum'),
                ]
            );
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'title'         => __('Error'),
                    'message'       => $exception->getMessage(),
                    'back_url'      => $context->backUrl ?? '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        if ($request->getMethod() === 'POST') {
            $action = $request->body('action', 'delete');
            $hardDelete = $action === 'delete' && $this->currentUser->rights === 9;
            $result = $this->deletePostUseCase->execute($context, $hardDelete, $this->getForumSettings());
            redirect($result->redirectUrl);
        }

        return new ViewResponse(
            '@forum/public/delete-post.twig',
            [
                'title'           => __('Delete Message'),
                'page_title'      => __('Delete Message'),
                'posts'           => $context->posts,
                'back_url'        => $context->backUrl,
                'can_hard_delete' => $this->currentUser->rights === 9,
                'delete_action'   => '/forum/delete-post/' . $id . '/',
            ]
        );
    }

    private function getForumSettings(): array
    {
        $setForumDefault = [
            'farea'    => 0,
            'upfp'     => 0,
            'preview'  => 1,
            'postclip' => 1,
            'postcut'  => 2,
        ];

        $setForum = [];
        if ($this->currentUser->isValid() && ! empty($this->currentUser->set_forum)) {
            $setForum = (array) $this->currentUser->set_forum;
        }

        return array_merge($setForumDefault, $setForum);
    }
}
