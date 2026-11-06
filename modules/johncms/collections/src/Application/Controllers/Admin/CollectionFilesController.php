<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use InvalidArgumentException;
use Johncms\Http\Request;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Services\CollectionFileChoices;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class CollectionFilesController
{
    public function __construct(private CollectionFileChoices $choices)
    {
    }

    public function __invoke(Request $request, int $collection_id, string $code): JsonResponse
    {
        try {
            return new JsonResponse(['files' => $this->choices->search($collection_id, $code, $request->queryParam('context') ?: null, $request->queryParam('q'))]);
        } catch (InvalidArgumentException) {
            return new JsonResponse(['error' => ['message' => d__('system', 'Wrong data')]], 400);
        } catch (CollectionItemFormException $exception) {
            return new JsonResponse(['error' => ['message' => implode(' ', array_merge(...array_values($exception->errors)))]], 422);
        }
    }
}
