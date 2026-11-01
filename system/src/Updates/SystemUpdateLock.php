<?php

declare(strict_types=1);

namespace Johncms\Updates;

use Johncms\Scheduler\ScheduleMutexInterface;
use RuntimeException;

final readonly class SystemUpdateLock
{
    public function __construct(private ScheduleMutexInterface $mutex)
    {
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function run(callable $operation): mixed
    {
        $lock = $this->mutex->acquire('johncms.system.update');
        if ($lock === null) {
            throw new RuntimeException('Cannot acquire the system update lock. Another update may be running.');
        }
        try {
            return $operation();
        } finally {
            $this->mutex->release($lock);
        }
    }
}
