<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Password;

/**
 * Builds the hasher from config/autoload/auth.*.php.
 *
 * A factory rather than arguments wired in services.php: the container is compiled and cached,
 * so an algorithm resolved while building it would be frozen into the cache and a changed
 * configuration would go on being ignored.
 */
final readonly class PasswordHasherFactory
{
    public function __construct(private LegacyMd5PasswordVerifier $legacy)
    {
    }

    public function __invoke(): PasswordHasherInterface
    {
        $options = config('auth.password.options', []);

        return new NativePasswordHasher(
            legacy: $this->legacy,
            algorithm: config('auth.password.algorithm', PASSWORD_DEFAULT),
            options: is_array($options) ? $options : [],
        );
    }
}
