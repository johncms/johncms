<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException;
use Johncms\Modules\Collections\Application\Extension\CollectionChangeHandlerInterface;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\ModuleRegistry;

final readonly class CollectionChangeDispatcher
{
    /** @var list<CollectionChangeHandlerInterface> */
    private array $handlers;

    /** @param iterable<CollectionChangeHandlerInterface> $handlers */
    public function __construct(
        iterable $handlers = [],
        private ?ModuleRegistry $modules = null,
        private ?CollectionTransactionInterface $transaction = null,
    ) {
        $this->handlers = [...$handlers];
    }

    public function ensureOwnerLoaded(?string $owner): void
    {
        if ($owner !== null && ! isset(($this->modules?->enabled() ?? [])[$owner])) {
            throw new CollectionDeletionException(['owner' => [d__('collections', 'Enable the owning module before deleting its content.')]]);
        }
    }

    public function dispatch(CollectionChangeDTO $change): void
    {
        foreach ($this->handlers as $handler) {
            $handler->withinTransaction($change);
        }
        if (! $change->imported) {
            foreach ($this->handlers as $handler) {
                if ($this->transaction === null) {
                    throw new \LogicException('Collection handlers require a transaction coordinator.');
                }
                $this->transaction->afterCommit(static fn () => $handler->afterCommit($change));
            }
        }
    }
}
