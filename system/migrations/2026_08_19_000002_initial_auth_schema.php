<?php

/**
 * The tables of the authentication layer as they stood when migrations were introduced.
 *
 * Part of the baseline, so it creates only what is missing: a site upgrading from 9.9 has none of
 * these tables, and one that already ran the former auth:upgrade-schema has all of them.
 */

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->createRoles();
        $this->createRolePermissions();
        $this->createUserRoles();
        $this->createAuthSessions();
        $this->createUserIdentities();
        $this->createAuthEvents();
        $this->createPasswordResetTokens();
        $this->widenIdentityAvatarUrl();
    }

    private function createRoles(): void
    {
        if ($this->schema->hasTable('roles')) {
            return;
        }

        $this->schema->create('roles', static function (TableDefinition $table): void {
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
        });
    }

    private function createRolePermissions(): void
    {
        if ($this->schema->hasTable('role_permissions')) {
            return;
        }

        $this->schema->create('role_permissions', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('role_id')->unsigned()->index();
            // The permission key as the code spells it, or a pattern such as 'forum.*'.
            // Kept even when the module declaring it is switched off, so that switching the
            // module back on restores what the role was granted.
            $table->string('permission', 128);
            $table->unique(['role_id', 'permission']);
        });
    }

    private function createUserRoles(): void
    {
        if ($this->schema->hasTable('user_roles')) {
            return;
        }

        $this->schema->create('user_roles', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index();
            $table->integer('role_id')->unsigned()->index();
            $table->integer('granted_by')->unsigned()->nullable();
            $table->integer('granted_at')->unsigned()->default(0);
            // Temporary moderation: the row stops counting on its own.
            $table->integer('expires_at')->unsigned()->nullable();
            $table->unique(['user_id', 'role_id']);
        });
    }

    private function createAuthSessions(): void
    {
        if ($this->schema->hasTable('auth_sessions')) {
            return;
        }

        $this->schema->create('auth_sessions', static function (TableDefinition $table): void {
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
        });
    }

    private function createUserIdentities(): void
    {
        if ($this->schema->hasTable('user_identities')) {
            return;
        }

        $this->schema->create('user_identities', static function (TableDefinition $table): void {
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
        });
    }

    private function createAuthEvents(): void
    {
        if ($this->schema->hasTable('auth_events')) {
            return;
        }

        $this->schema->create('auth_events', static function (TableDefinition $table): void {
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
        });
    }

    private function createPasswordResetTokens(): void
    {
        if ($this->schema->hasTable('password_reset_tokens')) {
            return;
        }

        $this->schema->create('password_reset_tokens', static function (TableDefinition $table): void {
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
        });
    }

    /**
     * Turns an existing `avatar_url` varchar into text.
     *
     * The column shipped as varchar(255) and VK immediately overflowed it, taking the whole
     * sign-in down with a database error. A table created here gets the right type from the
     * start; this is what carries the sites that already had one.
     */
    private function widenIdentityAvatarUrl(): void
    {
        $type = $this->schema->getColumnType('user_identities', 'avatar_url');

        if ($type === null || str_contains(strtolower($type), 'text')) {
            return;
        }

        $this->schema->alter('user_identities', static function (TableDefinition $table): void {
            $table->text('avatar_url')->nullable()->change();
        });
    }
};
