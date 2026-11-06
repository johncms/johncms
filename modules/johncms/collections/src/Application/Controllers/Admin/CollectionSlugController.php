<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use Illuminate\Support\Str;
use Johncms\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class CollectionSlugController
{
    public function __invoke(Request $request): JsonResponse
    {
        $name = mb_substr($request->body('name'), 0, 255);
        return new JsonResponse(data: ['slug' => mb_substr(Str::slug($name), 0, 240)], headers: ['Cache-Control' => 'no-store']);
    }
}
