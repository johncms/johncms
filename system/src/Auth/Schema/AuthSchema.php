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

    public const ROLES = 'roles';

    public const ROLE_PERMISSIONS = 'role_permissions';

    public const USER_ROLES = 'user_roles';

    public const AUTH_EVENTS = 'auth_events';

    public const USER_IDENTITIES = 'user_identities';

    public static function create(Builder $schema): void
    {
        self::createPasswordResetTokens($schema);
        self::createAuthSessions($schema);
        self::createRoles($schema);
        self::createRolePermissions($schema);
        self::createUserRoles($schema);
        self::createAuthEvents($schema);
        self::createUserIdentities($schema);
        self::widenIdentityAvatarUrl($schema);
    }

    /**
     * Turns an existing `avatar_url` varchar into text.
     *
     * The column shipped as varchar(255) and VK immediately overflowed it, taking the whole
     * sign-in down with a database error. New installations get the right type from
     * createUserIdentities(); this is what carries the ones that already ran the upgrade.
     */
    private static function widenIdentityAvatarUrl(Builder $schema): void
    {
        if (! $schema->hasTable(self::USER_IDENTITIES)) {
            return;
        }

        foreach ($schema->getColumns(self::USER_IDENTITIES) as $column) {
            if ($column['name'] !== 'avatar_url') {
                continue;
            }

            if (! str_contains(strtolower((string) $column['type']), 'text')) {
                $schema->table(
                    self::USER_IDENTITIES,
                    static function (Blueprint $table): void {
                        $table->text('avatar_url')->nullable()->change();
                    }
                );
            }

            return;
        }
    }

    private static function createRoles(Builder $schema): void
    {
        if ($schema->hasTable(self::ROLES)) {
            return;
        }

        $schema->create(
            self::ROLES,
            static function (Blueprint $table): void {
                $table->increments('id');
                // What checks and code refer to; unlike the name, it is never renamed.
                $table->string('slug', 64)->unique();
                $table->string('name', 191)->default('');
                // Who outranks whom: which roles a moderator may act on, and later which
                // accounts an administrator may browse as.
                $table->smallInteger('level')->unsigned()->default(0);
                // A built-in role: its slug and level are fixed and it cannot be deleted.
                $table->boolean('is_system')->default(false);
                // Applies to every signed-in visitor without a row in user_roles. That is what
                // keeps the table small: only the exceptions are stored.
                $table->boolean('is_default')->default(false);
                // The role of visitors who are not signed in. Exactly one row has this.
                $table->boolean('is_guest')->default(false);
                $table->integer('created_at')->unsigned()->default(0);
                $table->integer('updated_at')->unsigned()->default(0);
            }
        );
    }

    private static function createRolePermissions(Builder $schema): void
    {
        if ($schema->hasTable(self::ROLE_PERMISSIONS)) {
            return;
        }

        $schema->create(
            self::ROLE_PERMISSIONS,
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('role_id')->unsigned()->index();
                // The permission key as the code spells it, or a pattern such as 'forum.*'.
                // Kept even when the module declaring it is switched off, so that switching the
                // module back on restores what the role was granted.
                $table->string('permission', 128);
                $table->unique(['role_id', 'permission']);
            }
        );
    }

    private static function createUserRoles(Builder $schema): void
    {
        if ($schema->hasTable(self::USER_ROLES)) {
            return;
        }

        $schema->create(
            self::USER_ROLES,
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->index();
                $table->integer('role_id')->unsigned()->index();
                $table->integer('granted_by')->unsigned()->nullable();
                $table->integer('granted_at')->unsigned()->default(0);
                // Temporary moderation: the row stops counting on its own.
                $table->integer('expires_at')->unsigned()->nullable();
                $table->unique(['user_id', 'role_id']);
            }
        );
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

    private static function createUserIdentities(Builder $schema): void
    {
        if ($schema->hasTable(self::USER_IDENTITIES)) {
            return;
        }

        $schema->create(
            self::USER_IDENTITIES,
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->index();
                // The key of the provider as its class spells it. Rows of a provider whose module
                // has been removed are kept: the account may be linked to it again later, and a
                // key nothing knows must not be a reason to lose the link.
                $table->string('provider', 32);
                // A string rather than an integer: providers disagree about the shape of their
                // identifiers, and some of them are not numbers at all.
                $table->string('provider_user_id', 191);
                // A snapshot taken when the link was made, for reference only. What the provider
                // says today is asked again on every sign-in.
                $table->string('email', 191)->nullable();
                $table->string('nickname', 191)->nullable();
                // Text, not a varchar: the avatar of a VK account arrives as a signed URL with a
                // list of crops in the query string and runs to several hundred characters, and a
                // truncated one is not a picture — it is a broken link.
                $table->text('avatar_url')->nullable();
                $table->integer('linked_at')->unsigned();
                $table->integer('last_login_at')->unsigned()->nullable();
                // One account of the provider belongs to one user of the site, and one user has
                // at most one account per provider.
                $table->unique(['provider', 'provider_user_id']);
                $table->unique(['user_id', 'provider']);
            }
        );
    }

    private static function createAuthEvents(Builder $schema): void
    {
        if ($schema->hasTable(self::AUTH_EVENTS)) {
            return;
        }

        $schema->create(
            self::AUTH_EVENTS,
            static function (Blueprint $table): void {
                $table->increments('id');
                // Whom the event is about. Null when nobody was identified — a sign-in attempt on
                // a login that does not exist still belongs in the record.
                $table->integer('user_id')->unsigned()->nullable()->index();
                // Who did it, when that is somebody else: an administrator granting a role, or the
                // administrator behind an impersonated session.
                $table->integer('actor_id')->unsigned()->nullable()->index();
                $table->string('event', 64)->index();
                $table->string('ip', 45)->default('');
                $table->string('user_agent', 255)->default('');
                // Whatever the event needs beyond the columns: the role that was granted, why a
                // sign-in was refused. Free-form on purpose — a column per event would be a
                // schema change for every new kind of event.
                $table->json('context')->nullable();
                $table->integer('created_at')->unsigned()->index();
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
