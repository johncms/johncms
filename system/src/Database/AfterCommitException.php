<?php

declare(strict_types=1);

namespace Johncms\Database;

/** Data is already committed; retry the failed derived actions, not the original mutation. */
final class AfterCommitException extends \RuntimeException
{
    /** @param list<\Throwable> $failures */
    public function __construct(public readonly array $failures)
    {
        parent::__construct('Data was committed, but post-commit actions failed: ' . implode('; ', array_map(
            static fn (\Throwable $failure): string => $failure::class . ': ' . $failure->getMessage(),
            $failures
        )), previous: $failures[0] ?? null);
    }
}
