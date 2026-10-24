<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Modules\ModuleRegistry;

/**
 * The address of the profile of an account on the site, when the site has one. The profile is a
 * module and may be switched off, and the panel does not lead to a page that is not there.
 */
final readonly class PublicProfileLinks
{
    private const MODULE = 'johncms/profile';

    public function __construct(
        private ModuleRegistry $modules,
    ) {
    }

    public function urlOf(int $userId): ?string
    {
        return isset($this->modules->enabled()[self::MODULE]) ? '/profile/' . $userId : null;
    }
}
