<?php

declare(strict_types=1);

namespace Johncms\Updates;

use LogicException;

/** Trusted PHP update code grants this scope; request data cannot activate it. */
final class SystemOperationContext
{
    private int $depth = 0;
    private ?\Closure $reporter = null;

    public function isActive(): bool
    {
        return $this->depth > 0;
    }

    public function requireActive(): void
    {
        if ($this->depth === 0) {
            throw new LogicException('Historical imports require a trusted system update operation.');
        }
    }

    public function report(string $message): void
    {
        $this->requireActive();
        if ($this->reporter !== null) {
            ($this->reporter)($message);
        }
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @param (callable(string): void)|null $reporter
     * @return T
     */
    public function runTrusted(callable $operation, ?callable $reporter = null): mixed
    {
        $previous = $this->reporter;
        $this->reporter = $reporter === null ? $previous : \Closure::fromCallable($reporter);
        ++$this->depth;
        try {
            return $operation();
        } finally {
            --$this->depth;
            $this->reporter = $previous;
        }
    }
}
