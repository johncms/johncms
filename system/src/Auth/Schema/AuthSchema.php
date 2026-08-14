<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/**
 * The tables of the authentication layer, defined once.
 *
 * Three callers need them and must not disagree: the installer building a fresh site, the
 * one-time command upgrading an existing one, and the tests. A copy in any of the three would
 * drift, and the drift would only surface on somebody else's installation.
 *
 * Every method is safe to call again: it creates what is missing and leaves the rest alone.
 *
 * No foreign keys on purpose. The rest of the core schema has none, and a constraint pointing
 * at `users` would make these tables impossible to create on their own — which is exactly what
 * a unit test does.
 */
final class AuthSchema
{
    public const PASSWORD_RESET_TOKENS = 'password_reset_tokens';

    public const AUTH_SESSIONS = 'auth_sessions';

    public static function create(Builder $schema): void
    {
        self::createPasswordResetTokens($schema);
        self::createAuthSessions($schema);
    }

    private static function createAuthSessions(Builder $schema): void
    {
        if ($schema->hasTable(self::AUTH_SESSIONS)) {
            return;
        }

        $schema->create(
            self::AUTH_SESSIONS,
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->index();
                // Only the digest, like every other secret here: the cookie the visitor holds
                // is the single copy, so a leaked dump cannot be replayed as a sign-in.
                $table->string('token_hash', 64)->unique();
                $table->integer('created_at')->unsigned();
                // Moved forward at most once per renew_interval, together with expires_at.
                $table->integer('last_used_at')->unsigned()->index();
                $table->integer('expires_at')->unsigned()->index();
                // The cap that is never extended. Null means there is none, which is the
                // default: a cap would undo the sliding lifetime for the most active visitors.
                $table->integer('absolute_expires_at')->unsigned()->nullable();
                $table->boolean('remember')->default(false);
                // Textual, unlike the legacy ip columns: new tables are IPv6-ready from the start.
                $table->string('ip', 45)->default('');
                $table->string('user_agent', 255)->default('');
                $table->integer('revoked_at')->unsigned()->nullable();
                $table->string('revoked_reason', 32)->nullable();
                // Set when an administrator is browsing as this user. The session then belongs
                // to the user, while the audit trail still names who opened it.
                $table->integer('impersonator_id')->unsigned()->nullable();
                // The administrator's own session, restored when they return to themselves.
                $table->integer('parent_session_id')->unsigned()->nullable();
            }
        );
    }

    private static function createPasswordResetTokens(Builder $schema): void
    {
        if ($schema->hasTable(self::PASSWORD_RESET_TOKENS)) {
            return;
        }

        $schema->create(
            self::PASSWORD_RESET_TOKENS,
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->index();
                // The token never touches the database in the clear: what the visitor holds is
                // the only copy, so a leaked dump cannot be used to take an account over.
                $table->string('token_hash', 64)->unique();
                $table->integer('expires_at')->unsigned()->index();
                // Set when the token is spent, which is what makes it single-use.
                $table->integer('used_at')->unsigned()->nullable();
                // Also the rate limit: how long ago the last request was made.
                $table->integer('created_at')->unsigned()->index();
            }
        );
    }
}
