<?php

declare(strict_types=1);

namespace Johncms\Modules\Auth\Application\Services;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;
use Johncms\Auth\Authorization\SystemRole;

/**
 * Whether an account can be registered at all.
 */
final class RegistrationPermissions implements PermissionProviderInterface
{
    public const GROUP = 'registration';

    /**
     * Sign up for an account. Held by the guest role, which is the only one that can act on it:
     * a visitor who is signed in has nothing to register.
     */
    public const REGISTER = 'registration.register';

    public function permissions(): iterable
    {
        return [
            new PermissionDefinition(
                self::REGISTER,
                self::GROUP,
                d__('auth', 'Register an account'),
                d__('auth', 'Registration'),
                [SystemRole::Guest->value]
            ),
        ];
    }
}
