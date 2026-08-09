<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumUserAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\MarkAllTopicsReadUseCase;
use Johncms\Http\Request;
use Johncms\Validator\Validator;

final readonly class MarkAllTopicsReadController
{
    public function __construct(
        private EnsureForumUserAccessUseCase $forumUserAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private MarkAllTopicsReadUseCase $markAllTopicsReadUseCase,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        try {
            $this->forumUserAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse($exception);
        }

        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        if (! $validator->isValid()) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('Unread'),
                    'page_title'    => __('Unread'),
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/unread/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $this->markAllTopicsReadUseCase->execute();

        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'         => __('Unread'),
                'page_title'    => __('Unread'),
                'type'          => 'alert-success',
                'message'       => __('All topics marked as read'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Forum'),
            ]
        );
    }
}
