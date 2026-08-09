<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureEditPostAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetEditPostContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\RestorePostUseCase;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;
use Johncms\Validator\Validator;

final readonly class RestorePostController
{
    public function __construct(
        private User $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetEditPostContextUseCase $contextUseCase,
        private EnsureEditPostAccessUseCase $accessUseCase,
        private RestorePostUseCase $restorePostUseCase,
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
            $validator = new Validator(
                ['csrf_token' => $request->body('csrf_token', '')],
                ['csrf_token' => ['Csrf']]
            );

            if (! $validator->isValid()) {
                return new ViewResponse(
                    '@theme/pages/result.twig',
                    [
                        'title'         => __('Restore Message'),
                        'type'          => 'alert-danger',
                        'message'       => __('Wrong data'),
                        'back_url'      => '/forum/restore-post/' . $id . '/',
                        'back_url_name' => __('Back'),
                    ]
                );
            }

            $this->restorePostUseCase->execute($context);
            redirect($context->backUrl);
        }

        return new ViewResponse(
            '@forum/public/restore-post.twig',
            [
                'title'          => __('Restore Message'),
                'page_title'     => __('Restore Message'),
                'back_url'       => $context->backUrl,
                'restore_action' => '/forum/restore-post/' . $id . '/',
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
