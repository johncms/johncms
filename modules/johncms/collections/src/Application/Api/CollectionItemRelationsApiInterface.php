<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

/** Trusted owner operations that must serialize with item deletion and publication changes. */
interface CollectionItemRelationsApiInterface
{
    /**
     * Serialize related cleanup with content writes; also runs after collection deletion.
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function withCollectionLock(int $collectionId, callable $operation): mixed;

    /**
     * @template T
     * @param callable(): T $operation Authorization remains with the consumer; publishedOnly applies the C4 policy using current reads.
     * @return T
     */
    public function withItemLock(int $collectionId, int $itemId, callable $operation, bool $publishedOnly = false): mixed;
}
