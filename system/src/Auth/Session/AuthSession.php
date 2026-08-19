<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Session;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Johncms\Auth\AuthTables;

/**
 * One signed-in device: the digest of the cookie it holds, when it was last seen and when it
 * stops working.
 *
 * A row per device is what the cookie alone could never give — signing out elsewhere, the list
 * of active sessions in the profile, and dropping every session when a password changes.
 *
 * @mixin Builder
 * @property int         $id
 * @property int         $user_id
 * @property string      $token_hash
 * @property int         $created_at
 * @property int         $last_used_at
 * @property int         $expires_at
 * @property int|null    $absolute_expires_at
 * @property bool        $remember
 * @property string      $ip
 * @property string      $user_agent
 * @property int|null    $revoked_at
 * @property string|null $revoked_reason
 * @property int|null    $impersonator_id
 * @property int|null    $parent_session_id
 *
 * @property bool $is_impersonation
 */
class AuthSession extends Model
{
    protected $table = AuthTables::AUTH_SESSIONS;

    public $timestamps = false;

    protected $casts = [
        'remember' => 'bool',
    ];

    protected $fillable = [
        'user_id',
        'token_hash',
        'created_at',
        'last_used_at',
        'expires_at',
        'absolute_expires_at',
        'remember',
        'ip',
        'user_agent',
        'revoked_at',
        'revoked_reason',
        'impersonator_id',
        'parent_session_id',
    ];

    public function getIsImpersonationAttribute(): bool
    {
        return $this->impersonator_id !== null;
    }

    /**
     * Whether the session may still answer for its user at the given moment: not signed out,
     * not expired, and within the absolute cap when one is set.
     */
    public function isUsableAt(int $timestamp): bool
    {
        if ($this->revoked_at !== null || $this->expires_at <= $timestamp) {
            return false;
        }

        return $this->absolute_expires_at === null || $this->absolute_expires_at > $timestamp;
    }
}
