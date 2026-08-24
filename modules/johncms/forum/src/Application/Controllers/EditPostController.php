<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\AttachUploadedFilesToMessageUseCase;
use Johncms\Modules\Forum\Application\UseCases\EditPostUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureEditPostAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetEditPostContextUseCase;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\System\Utility\EditorContentNormalizer;

final readonly class EditPostController
{
    public function __construct(
        private EditorContentNormalizer $editorContentNormalizer,
        private CurrentUser $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetEditPostContextUseCase $contextUseCase,
        private EnsureEditPostAccessUseCase $accessUseCase,
        private EditPostUseCase $editPostUseCase,
        private AttachUploadedFilesToMessageUseCase $attachUploadedFilesUseCase,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        $page = max(1, $request->queryInt('page', 1));

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

        if ($request->hasBody('submit')) {
            $msg = $this->editorContentNormalizer->trimEdgeEmptyBlocks($request->body('msg', ''));
            $msg = trim($msg);
            $attachedFiles = (array) $request->bodyInts('attached_files');
            if ($msg === '') {
                return new ViewResponse(
                    '@theme/pages/result.twig',
                    [
                        'title'         => __('Edit Message'),
                        'type'          => 'alert-danger',
                        'message'       => __('You have not entered the message'),
                        'back_url'      => '/forum/edit-post/' . $id . '/' . ($page > 1 ? '?page=' . $page : ''),
                        'back_url_name' => __('Repeat'),
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

        $message = ! $request->hasBody('msg')
            ? (string) $context->message->getRawOriginal('text')
            : $request->body('msg');

        return new ViewResponse(
            '@forum/public/edit-post.twig',
            [
                'title'      => __('Edit Message'),
                'page_title' => __('Edit Message'),
                'action_url' => '/forum/edit-post/' . $id . '/' . ($page > 1 ? '?page=' . $page : ''),
                'msg'        => $message,
                'back_url'   => $context->backUrl,
                'errors'     => [],
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
        if ($this->currentUser->isValid() && ! empty($this->currentUser->user()->set_forum)) {
            $setForum = (array) $this->currentUser->user()->set_forum;
        }

        return array_merge($setForumDefault, $setForum);
    }
}
