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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Johncms\Auth\AuthTables;

/**
 * A password recovery request: the digest of the link that was e-mailed, when it stops working
 * and whether it has been spent.
 *
 * @mixin Builder
 * @property int      $id
 * @property int      $user_id
 * @property string   $token_hash
 * @property int      $expires_at
 * @property int|null $used_at
 * @property int      $created_at
 */
class PasswordResetToken extends Model
{
    protected $table = AuthTables::PASSWORD_RESET_TOKENS;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
        'used_at',
        'created_at',
    ];
}
