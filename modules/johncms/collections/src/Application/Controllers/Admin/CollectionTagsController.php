<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use Johncms\Http\Request;
use Johncms\Modules\Collections\Application\Api\CollectionsUserApiInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class CollectionTagsController
{
    public function __construct(private CollectionsUserApiInterface $api)
    {
    }

    public function __invoke(Request $request, int $collection_id, string $code): JsonResponse
    {
        try {
            return new JsonResponse(['tags' => $this->api->suggestTags($collection_id, $code, $request->queryParam('q'))]);
        } catch (\InvalidArgumentException) {
            return new JsonResponse(['error' => ['message' => d__('system', 'Wrong data')]], 400);
        }
    }
}
