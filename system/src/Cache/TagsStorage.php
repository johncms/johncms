<?php

declare(strict_types=1);

namespace Johncms\Cache;

/**
 * How the filesystem driver keeps the tag to entry relation.
 */
enum TagsStorage: string
{
    /** Symlinks when the hosting allows them, plain files otherwise. */
    case Auto = 'auto';

    /** Symlinks: the fastest, but unavailable on some shared hostings and on Windows. */
    case Symlink = 'symlink';

    /** Plain files: slower on invalidation, works anywhere. */
    case Files = 'files';
}
