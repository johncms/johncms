<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Impersonation;

/**
 * Reads the impersonation settings out of config/autoload/auth.*.php.
 *
 * A factory for the same reason SessionSettingsFactory is one: the container is compiled and
 * cached, so a value resolved while building it would be frozen into the cache.
 */
final class ImpersonationSettingsFactory
{
    public function __invoke(): ImpersonationSettings
    {
        $defaults = new ImpersonationSettings();

        /** @var list<string> $denied */
        $denied = array_values(
            array_filter(
                (array) config('auth.impersonation.denied_permissions', $defaults->deniedPermissions),
                'is_string'
            )
        );

        return new ImpersonationSettings(
            parentCookieName: (string) config('auth.impersonation.parent_cookie_name', $defaults->parentCookieName),
            lifetime: (int) config('auth.impersonation.lifetime', $defaults->lifetime),
            deniedPermissions: $denied,
        );
    }
}
