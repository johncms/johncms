<?php

declare(strict_types=1);

namespace Johncms\Checker;

final readonly class NativeFilesystemProbe implements FilesystemProbeInterface
{
    public function canWriteDirectory(string $path): bool
    {
        if ($path === '') {
            return false;
        }
        $directory = $this->existingParent($path);

        return is_dir($directory) && is_writable($directory) && is_executable($directory);
    }

    public function diskSpace(string $path): ?array
    {
        if ($path === '') {
            return null;
        }
        $directory = $this->existingParent($path);
        $free = @disk_free_space($directory);
        $total = @disk_total_space($directory);
        if ($free === false || $total === false || $total <= 0) {
            return null;
        }

        return ['free' => (int) $free, 'total' => (int) $total];
    }

    private function existingParent(string $path): string
    {
        clearstatcache(true, $path);
        while (! file_exists($path) && ! is_link($path)) {
            $parent = dirname($path);
            if ($parent === $path) {
                break;
            }
            $path = $parent;
        }

        return $path;
    }
}
