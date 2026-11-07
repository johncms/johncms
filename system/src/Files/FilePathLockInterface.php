<?php

declare(strict_types=1);

namespace Johncms\Files;

/**
 * Serializes the work on one stored path.
 *
 * Identical contents share a path, so storing a copy and deleting the last row of another copy
 * meet on the same bytes: one asks whether the file is there, the other whether anybody still
 * points at it. Both answers are only true while nobody else acts on the path.
 */
interface FilePathLockInterface
{
    /**
     * Run the action while no other process works on the path.
     *
     * @template T
     * @param callable(): T $action
     * @return T
     */
    public function synchronized(string $storage, string $path, callable $action): mixed;
}
