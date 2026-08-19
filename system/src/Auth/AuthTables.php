<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth;

/**
 * The names of the tables the authentication layer keeps its data in.
 *
 * Only the names: what the tables look like is decided by the migrations, and this is what the
 * models and the queries that join them spell the names with, so a name is written down once.
 */
final class AuthTables
{
    public const string PASSWORD_RESET_TOKENS = 'password_reset_tokens';

    public const string AUTH_SESSIONS = 'auth_sessions';

    public const string ROLES = 'roles';

    public const string ROLE_PERMISSIONS = 'role_permissions';

    public const string USER_ROLES = 'user_roles';

    public const string AUTH_EVENTS = 'auth_events';

    public const string USER_IDENTITIES = 'user_identities';
}
