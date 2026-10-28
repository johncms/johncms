<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\ModuleRegistry;

/**
 * The profiles are open to members only, and the profile is a module that may be switched off.
 */
final readonly class ProfileLinks implements ProfileLinksInterface
{
    private const string MODULE = 'johncms/profile';

    public function __construct(
        private CurrentUser $currentUser,
        private ModuleRegistry $modules,
    ) {
    }

    public function canView(int $userId): bool
    {
        return $userId > 0 && $this->currentUser->isValid();
    }

    public function urlOf(int $userId): ?string
    {
        if (! $this->canView($userId) || ! isset($this->modules->enabled()[self::MODULE])) {
            return null;
        }

        return '/profile/' . $userId;
    }
}
