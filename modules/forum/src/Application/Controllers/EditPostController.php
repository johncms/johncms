<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\AttachUploadedFilesToMessageUseCase;
use Johncms\Modules\Forum\Application\UseCases\EditPostUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureEditPostAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetEditPostContextUseCase;
use Johncms\Security\Csrf;
use Johncms\Http\Request;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;

final readonly class EditPostController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private EditorContentNormalizer $editorContentNormalizer,
        private Csrf $csrf,
        private User $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetEditPostContextUseCase $contextUseCase,
        private EnsureEditPostAccessUseCase $accessUseCase,
        private EditPostUseCase $editPostUseCase,
        private AttachUploadedFilesToMessageUseCase $attachUploadedFilesUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): string
    {
        $page = max(1, $this->request->queryInt('page', 1));

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

        if ($this->request->hasBody('submit')) {
            $msg = $this->editorContentNormalizer->trimEdgeEmptyBlocks($this->request->body('msg', ''));
            $msg = trim($msg);
            $attachedFiles = (array) $this->request->bodyInts('attached_files');
            if ($msg === '') {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Edit Message'),
                        'type'          => 'alert-danger',
                        'message'       => __('You have not entered the message'),
                        'back_url'      => '/forum/edit-post/' . $id . '/' . ($page > 1 ? '?page=' . $page : ''),
                        'back_url_name' => __('Repeat'),
                    ]
                );
            }

            $validator = new Validator(
                ['csrf_token' => $this->request->body('csrf_token', '')],
                ['csrf_token' => ['Csrf']]
            );
            if (! $validator->isValid()) {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Edit Message'),
                        'type'          => 'alert-danger',
                        'message'       => __('Wrong data'),
                        'back_url'      => '/forum/edit-post/' . $id . '/',
                        'back_url_name' => __('Back'),
                    ]
                );
            }

            $this->editPostUseCase->execute($context, $msg);
            $this->attachUploadedFilesUseCase->execute(
                messageId: $context->message->id,
                attachedFileIds: $attachedFiles,
            );
            redirect($context->topic->url . ($context->page > 1 ? '?page=' . $context->page : ''));
        }

        $message = ! $this->request->hasBody('msg')
            ? (string) $context->message->getRawOriginal('text')
            : $this->request->body('msg');

        return $this->render->render(
            'forum::edit_post',
            [
                'title'          => __('Edit Message'),
                'page_title'     => __('Edit Message'),
                'id'             => $id,
                'msg'            => $message,
                'page'           => $page,
                'back_url'       => $context->backUrl,
                'settings_forum' => $this->getForumSettings(),
                'csrf_token'     => $this->csrf->getToken(),
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
