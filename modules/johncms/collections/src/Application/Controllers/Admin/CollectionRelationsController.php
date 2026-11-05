<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use Johncms\Http\Request;
use Johncms\Modules\Collections\Application\Services\CollectionRelationChoices;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class CollectionRelationsController
{
    public function __construct(private CollectionRelationChoices $choices)
    {
    }

    public function __invoke(Request $request, int $collection_id, string $code): JsonResponse
    {
        try {
            return new JsonResponse(['options' => $this->choices->search($collection_id, $code, $request->queryParam('q'))]);
        } catch (\InvalidArgumentException) {
            return new JsonResponse(['error' => ['message' => d__('system', 'Wrong data')]], 400);
        }
    }
}
