<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Http\EditorImageUploadResponder;
use Johncms\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class UploadFileController
{
    public function __construct(
        private EditorImageUploadResponder $responder,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return $this->responder->store($request, 'guestbook', 'guestbook');
    }
}
