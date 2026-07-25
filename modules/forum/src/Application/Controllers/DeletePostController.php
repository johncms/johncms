<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\DeletePostUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureEditPostAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetEditPostContextUseCase;
use Johncms\Security\Csrf;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeletePostController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private Csrf $csrf,
        private User $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetEditPostContextUseCase $contextUseCase,
        private EnsureEditPostAccessUseCase $accessUseCase,
        private DeletePostUseCase $deletePostUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): Response
    {
        try {
            $context = $this->contextUseCase->execute($id, $this->getForumSettings());
            $this->accessUseCase->execute($context);
        } catch (ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'title'         => __('Error'),
                    'message'       => __('Message does not exists or has been deleted'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Forum'),
                ]
            );
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'title'         => __('Error'),
                    'message'       => $exception->getMessage(),
                    'back_url'      => $context->backUrl ?? '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        if ($this->request->getMethod() === 'POST') {
            $validator = new Validator(
                ['csrf_token' => $this->request->body('csrf_token', '')],
                ['csrf_token' => ['Csrf']]
            );

            if (! $validator->isValid()) {
                return new Response(
                    $this->render->render(
                        'system::pages/result',
                        [
                            'title'         => __('Delete Message'),
                            'type'          => 'alert-danger',
                            'message'       => __('Wrong data'),
                            'back_url'      => '/forum/delete-post/' . $id . '/',
                            'back_url_name' => __('Back'),
                        ]
                    )
                );
            }

            $action = $this->request->body('action', 'delete');
            $hardDelete = $action === 'delete' && $this->currentUser->rights === 9;
            $result = $this->deletePostUseCase->execute($context, $hardDelete, $this->getForumSettings());
            redirect($result->redirectUrl);
        }

        return new Response(
            $this->render->render(
                'forum::delete_post',
                [
                    'title'           => __('Delete Message'),
                    'page_title'      => __('Delete Message'),
                    'id'              => $id,
                    'posts'           => $context->posts,
                    'back_url'        => $context->backUrl,
                    'csrf_token'      => $this->csrf->getToken(),
                    'can_hard_delete' => $this->currentUser->rights === 9,
                    'delete_action'   => '/forum/delete-post/' . $id . '/',
                ]
            )
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
