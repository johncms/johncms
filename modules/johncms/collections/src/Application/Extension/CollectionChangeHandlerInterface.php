<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Extension;

use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;

interface CollectionChangeHandlerInterface
{
    /** Required related-row changes share the mutation's transaction and may abort it. */
    public function withinTransaction(CollectionChangeDTO $change): void;

    /** Derived work runs only after the outer commit; historical imports skip this phase. */
    public function afterCommit(CollectionChangeDTO $change): void;
}
