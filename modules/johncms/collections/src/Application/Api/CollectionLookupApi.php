<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\Collections\Application\Services\CollectionApiMapper;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;

final readonly class CollectionLookupApi implements CollectionLookupApiInterface
{
    public function __construct(private ContentCollectionRepositoryInterface $collections, private CollectionApiMapper $mapper)
    {
    }

    public function findDeclared(string $owner, string $key): ?CollectionRecordDTO
    {
        $collection = $this->collections->findByDeclaration($owner, $key);
        return $collection !== null ? $this->mapper->collection($collection) : null;
    }
}
