<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\DeletePostFileUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureEditPostAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetDeletePostFileContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetEditPostContextUseCase;
use Johncms\Security\Csrf;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeletePostFileController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Csrf $csrf,
        private User $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetEditPostContextUseCase $contextUseCase,
        private EnsureEditPostAccessUseCase $accessUseCase,
        private GetDeletePostFileContextUseCase $fileContextUseCase,
        private DeletePostFileUseCase $deletePostFileUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(Request $request, int $id, int $fid): Response
    {
        try {
            $context = $this->contextUseCase->execute($id, $this->getForumSettings());
            $this->accessUseCase->execute($context);
            $file = $this->fileContextUseCase->execute($id, $fid);
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

        if ($request->getMethod() === 'POST' && $request->hasBody('delfile')) {
            $validator = new Validator(
                ['csrf_token' => $request->body('csrf_token', '')],
                ['csrf_token' => ['Csrf']]
            );

            if (! $validator->isValid()) {
                return new Response(
                    $this->render->render(
                        'system::pages/result',
                        [
                            'title'         => __('Delete file'),
                            'type'          => 'alert-danger',
                            'message'       => __('Wrong data'),
                            'back_url'      => '/forum/delete-post-file/' . $id . '/' . $fid . '/',
                            'back_url_name' => __('Back'),
                        ]
                    )
                );
            }

            $this->deletePostFileUseCase->execute($file->id, $file->filename);
            redirect($context->backUrl);
        }

        return new Response(
            $this->render->render(
                'forum::delete_file',
                [
                    'title'         => __('Delete file'),
                    'page_title'    => __('Delete file'),
                    'id'            => $id,
                    'fid'           => $fid,
                    'back_url'      => $context->backUrl,
                    'csrf_token'    => $this->csrf->getToken(),
                    'delete_action' => '/forum/delete-post-file/' . $id . '/' . $fid . '/',
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
