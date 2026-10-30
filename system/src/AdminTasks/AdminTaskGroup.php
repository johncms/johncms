<?php

declare(strict_types=1);

namespace Johncms\AdminTasks;

/**
 * When a maintenance task is wanted, which is how the maintenance screen groups them.
 */
enum AdminTaskGroup: string
{
    /** Cleaning up and the caches: safe at any time. */
    case Routine = 'routine';

    /** Bringing the site up to its files after they were updated. */
    case Update = 'update';
}
