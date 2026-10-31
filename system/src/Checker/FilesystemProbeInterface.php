<?php

declare(strict_types=1);

namespace Johncms\Checker;

interface FilesystemProbeInterface
{
    /** Whether the directory is writable, or can be created below a writable directory. */
    public function canWriteDirectory(string $path): bool;

    /** @return array{free: int, total: int}|null Null when the filesystem cannot be measured. */
    public function diskSpace(string $path): ?array;
}
