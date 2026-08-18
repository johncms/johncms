<?php

declare(strict_types=1);

namespace Johncms\Cache;

/**
 * The storage the cache pool is built on, chosen by config/autoload/cache.*.php.
 */
enum CacheDriver: string
{
    /** Files under data/cache — works everywhere, the default for a fresh installation. */
    case Filesystem = 'filesystem';

    /** Shared memory of the PHP process. Fast, but local to one web server. */
    case Apcu = 'apcu';

    /** Redis, the option for an installation spread over several servers. */
    case Redis = 'redis';

    /** In-memory for the lifetime of one request. Meant for tests. */
    case Memory = 'array';

    /** Stores nothing: every read is a miss. Useful while debugging. */
    case Disabled = 'null';
}
