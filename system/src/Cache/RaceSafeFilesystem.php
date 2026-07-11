<?php

declare(strict_types=1);

namespace Johncms\Cache;

use Illuminate\Filesystem\Filesystem;

/**
 * Filesystem for the file cache store that tolerates cache files being
 * deleted between the existence check and the read (e.g. during cache clearing).
 */
final class RaceSafeFilesystem extends Filesystem
{
    public function sharedGet($path)
    {
        $contents = '';

        // The file may disappear after the isFile() check in get(),
        // so a failed fopen() here is an expected cache miss, not an error.
        $handle = @fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    // fstat() reads the size from the open handle, so it works
                    // even if the file was unlinked after fopen().
                    $size = fstat($handle)['size'] ?? 0;

                    if ($size > 0) {
                        $contents = fread($handle, $size);
                    }

                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $contents;
    }
}
