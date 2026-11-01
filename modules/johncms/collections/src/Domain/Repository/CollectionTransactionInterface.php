<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Repository;

interface CollectionTransactionInterface
{
    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function run(callable $operation): mixed;
}
