<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\External;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Johncms\Auth\AuthTables;

/**
 * One account of an external service linked to an account of the site.
 *
 * @mixin Builder
 * @property int         $id
 * @property int         $user_id
 * @property string      $provider
 * @property string      $provider_user_id
 * @property string|null $email
 * @property string|null $nickname
 * @property string|null $avatar_url
 * @property int         $linked_at
 * @property int|null    $last_login_at
 */
class UserIdentity extends Model
{
    protected $table = AuthTables::USER_IDENTITIES;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'email',
        'nickname',
        'avatar_url',
        'linked_at',
        'last_login_at',
    ];
}
