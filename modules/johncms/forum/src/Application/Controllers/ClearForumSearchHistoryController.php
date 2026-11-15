<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\ClearForumSearchHistoryUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumUserAccessUseCase;
use Johncms\Http\View\ViewResponse;

final readonly class ClearForumSearchHistoryController
{
    public function __construct(
        private EnsureForumUserAccessUseCase $forumUserAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ClearForumSearchHistoryUseCase $clearForumSearchHistoryUseCase,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        try {
            $this->forumUserAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse($exception);
        }

        $this->clearForumSearchHistoryUseCase->execute();
        redirect('/forum/search/');
    }
}
