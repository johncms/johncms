<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumUserAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\MarkAllTopicsReadUseCase;
use Johncms\Http\Request;

final readonly class MarkAllTopicsReadController
{
    public function __construct(
        private EnsureForumUserAccessUseCase $forumUserAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private MarkAllTopicsReadUseCase $markAllTopicsReadUseCase,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        try {
            $this->forumUserAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse($exception);
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
