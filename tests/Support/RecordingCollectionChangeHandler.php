<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\Extension\CollectionChangeHandlerInterface;

final class RecordingCollectionChangeHandler implements CollectionChangeHandlerInterface
{
    /** @var list<CollectionChangeDTO> */
    public array $within = [];
    /** @var list<CollectionChangeDTO> */
    public array $committed = [];
    public bool $failWithin = false;
    public bool $failAfter = false;

    public function withinTransaction(CollectionChangeDTO $change): void
    {
        $this->within[] = $change;
        if ($this->failWithin) {
            throw new \RuntimeException('Required collection handler failed.');
        }
    }

    public function afterCommit(CollectionChangeDTO $change): void
    {
        $this->committed[] = $change;
        if ($this->failAfter) {
            throw new \RuntimeException('Derived collection handler failed.');
        }
    }
}
