<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;

/**
 * Counts how often the registry reads it, so the lazy one-time collection can be asserted on.
 */
final class CountingPermissionProvider implements PermissionProviderInterface
{
    public int $calls = 0;

    public function permissions(): iterable
    {
        ++$this->calls;

        return [new PermissionDefinition('forum.topic.delete', 'forum', 'Delete topics')];
    }
}
