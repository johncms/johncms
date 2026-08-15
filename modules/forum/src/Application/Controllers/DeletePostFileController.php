<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\DeletePostFileUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureEditPostAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetDeletePostFileContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetEditPostContextUseCase;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;

final readonly class DeletePostFileController
{
    public function __construct(
        private CurrentUser $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetEditPostContextUseCase $contextUseCase,
        private EnsureEditPostAccessUseCase $accessUseCase,
        private GetDeletePostFileContextUseCase $fileContextUseCase,
        private DeletePostFileUseCase $deletePostFileUseCase,
    ) {
    }

    public function __invoke(Request $request, int $id, int $fid): ViewResponse
    {
        try {
            $context = $this->contextUseCase->execute($id, $this->getForumSettings());
            $this->accessUseCase->execute($context);
            $file = $this->fileContextUseCase->execute($id, $fid);
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

        if ($request->getMethod() === 'POST' && $request->hasBody('delfile')) {
            $this->deletePostFileUseCase->execute($file->id, $file->filename);
            redirect($context->backUrl);
        }

        return new ViewResponse(
            '@forum/public/delete-post-file.twig',
            [
                'title'         => __('Delete file'),
                'page_title'    => __('Delete file'),
                'back_url'      => $context->backUrl,
                'delete_action' => '/forum/delete-post-file/' . $id . '/' . $fid . '/',
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
