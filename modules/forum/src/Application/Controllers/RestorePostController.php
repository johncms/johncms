<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureEditPostAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetEditPostContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\RestorePostUseCase;
use Johncms\Security\Csrf;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Symfony\Component\HttpFoundation\Response;

final readonly class RestorePostController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Csrf $csrf,
        private User $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetEditPostContextUseCase $contextUseCase,
        private EnsureEditPostAccessUseCase $accessUseCase,
        private RestorePostUseCase $restorePostUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(Request $request, int $id): Response
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

        if ($request->getMethod() === 'POST') {
            $validator = new Validator(
                ['csrf_token' => $request->body('csrf_token', '')],
                ['csrf_token' => ['Csrf']]
            );

            if (! $validator->isValid()) {
                return new Response(
                    $this->render->render(
                        'system::pages/result',
                        [
                            'title'         => __('Restore Message'),
                            'type'          => 'alert-danger',
                            'message'       => __('Wrong data'),
                            'back_url'      => '/forum/restore-post/' . $id . '/',
                            'back_url_name' => __('Back'),
                        ]
                    )
                );
            }

            $this->restorePostUseCase->execute($context);
            redirect($context->backUrl);
        }

        return new Response(
            $this->render->render(
                'forum::restore_post',
                [
                    'title'          => __('Restore Message'),
                    'page_title'     => __('Restore Message'),
                    'back_url'       => $context->backUrl,
                    'restore_action' => '/forum/restore-post/' . $id . '/',
                    'csrf_token'     => $this->csrf->getToken(),
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
