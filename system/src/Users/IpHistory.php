<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class User
 *
 * @mixin Builder
 * @property int $id
 * @property int $user_id
 * @property int $ip
 * @property int $ip_via_proxy
 * @property int $time
 */
class IpHistory extends Model
{
    protected $table = 'cms_users_iphistory';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip',
        'ip_via_proxy',
        'time',
    ];
}
