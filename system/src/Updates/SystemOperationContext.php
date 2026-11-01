<?php

declare(strict_types=1);

namespace Johncms\Updates;

use LogicException;

/** Trusted PHP update code grants this scope; request data cannot activate it. */
final class SystemOperationContext
{
    private int $depth = 0;

    public function requireActive(): void
    {
        if ($this->depth === 0) {
            throw new LogicException('Historical imports require a trusted system update operation.');
        }
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function runTrusted(callable $operation): mixed
    {
        ++$this->depth;
        try {
            return $operation();
        } finally {
            --$this->depth;
        }
    }
}
