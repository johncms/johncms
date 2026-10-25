<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers\Admin;

use Johncms\Http\EditorImageUploadResponder;
use Johncms\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * A picture the editor of an article uploads while the form is open.
 */
final readonly class ArticleUploadController
{
    public function __invoke(Request $request, EditorImageUploadResponder $responder): JsonResponse
    {
        return $responder->store($request, 'news', 'news');
    }
}
