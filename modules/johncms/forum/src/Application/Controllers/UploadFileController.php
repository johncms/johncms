<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Http\EditorImageUploadResponder;
use Johncms\Http\Request;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class UploadFileController
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private CurrentUser $currentUser,
        private EditorImageUploadResponder $responder,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException) {
            return $this->responder->accessDenied();
        }

        if (! $this->mayPost()) {
            return $this->responder->accessDenied();
        }

        return $this->responder->store($request, 'forum_files', 'forum');
    }

    private function mayPost(): bool
    {
        if (! $this->currentUser->isValid()) {
            return false;
        }

        $bans = $this->currentUser->user()->ban;

        return ! isset($bans[1]) && ! isset($bans[11]) && $this->accessChecker->allows(ForumPermissions::POST);
    }
}
