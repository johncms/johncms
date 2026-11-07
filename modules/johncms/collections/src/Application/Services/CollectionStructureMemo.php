<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Database\TransactionCallbacks;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Keeps what a request has already read about the structure of collections: the collection
 * itself, its fields and its sections. A page built of several lists of one collection would
 * otherwise read them again for every list.
 *
 * Forgotten at the start of every request, after every committed change of a collection, and
 * never used inside a transaction, where a write must see what is actually stored.
 */
final class CollectionStructureMemo implements ResetInterface
{
    /** @var array<string, mixed> */
    private array $values = [];

    /**
     * @template T
     * @param callable(): T $load
     * @return T
     */
    public function remember(string $key, callable $load): mixed
    {
        if (TransactionCallbacks::hasActiveTransaction()) {
            return $load();
        }
        if (! array_key_exists($key, $this->values)) {
            $this->values[$key] = $load();
        }
        return $this->values[$key];
    }

    public function reset(): void
    {
        $this->values = [];
    }
}
