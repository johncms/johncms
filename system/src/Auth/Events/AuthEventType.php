<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Events;

/**
 * What the audit trail records.
 *
 * The values are what ends up in the column and in the reports, so they are never renamed: an
 * old row must keep meaning what it meant when it was written. Modules are free to log their own
 * strings — the enum only names the events the core itself produces.
 */
enum AuthEventType: string
{
    case LoginSuccess = 'login.success';

    case LoginFailed = 'login.failed';

    case Logout = 'logout';

    case PasswordChanged = 'password.changed';

    case PasswordResetRequested = 'password.reset.requested';

    case PasswordResetCompleted = 'password.reset.completed';

    /** A device closed from "my devices" or by an administrator, as opposed to a plain sign-out. */
    case SessionRevoked = 'session.revoked';

    case RoleGranted = 'role.granted';

    case RoleRevoked = 'role.revoked';

    case ImpersonationStart = 'impersonation.start';

    case ImpersonationStop = 'impersonation.stop';

    /** A request that changed something while an administrator was browsing as somebody else. */
    case ImpersonatedAction = 'impersonation.action';

    /**
     * A human-readable label for the admin log, translated at call time rather than stored.
     */
    public function label(): string
    {
        return match ($this) {
            self::LoginSuccess => d__('system', 'Signed in'),
            self::LoginFailed => d__('system', 'Sign-in failed'),
            self::Logout => d__('system', 'Signed out'),
            self::PasswordChanged => d__('system', 'Password changed'),
            self::PasswordResetRequested => d__('system', 'Password recovery requested'),
            self::PasswordResetCompleted => d__('system', 'Password recovered'),
            self::SessionRevoked => d__('system', 'Device signed out'),
            self::RoleGranted => d__('system', 'Role granted'),
            self::RoleRevoked => d__('system', 'Role revoked'),
            self::ImpersonationStart => d__('system', 'Started browsing as the user'),
            self::ImpersonationStop => d__('system', 'Stopped browsing as the user'),
            self::ImpersonatedAction => d__('system', 'Action while browsing as the user'),
        };
    }
}
