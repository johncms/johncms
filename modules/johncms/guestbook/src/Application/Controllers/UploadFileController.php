<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Http\EditorImageUploadResponder;
use Johncms\Http\Request;
use Johncms\Modules\Guestbook\Application\Uploads\GuestbookEditorUploads;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class UploadFileController
{
    public function __construct(
        private EditorImageUploadResponder $responder,
        private CurrentUser $currentUser,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return $this->responder->storeTracked($request, GuestbookEditorUploads::OWNER_TYPE, $this->currentUser->id());
    }
}
