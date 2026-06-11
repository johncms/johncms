<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Johncms\Casts\Ip;
use Johncms\Casts\SpecialChars;
use Johncms\Casts\TimeToDate;
use Johncms\Users\User;

/**
 * Class Guestbook
 *
 * @package Guestbook\Models
 *
 * @mixin Builder
 * @property int $id
 * @property bool $adm
 * @property int $time
 * @property int $user_id
 * @property string $name
 * @property string $text
 * @property int $ip
 * @property string $browser
 * @property string $admin
 * @property string $otvet
 * @property int $otime
 * @property string $edit_who
 * @property int $edit_time
 * @property int $edit_count
 * @property array $attached_files
 *
 * @property User|null $user
 * @property bool $is_online
 */
class GuestbookEntry extends Model
{
    protected $table = 'guest';

    public $timestamps = false;

    protected $casts = [
        'adm'            => 'boolean',
        'time'           => TimeToDate::class,
        'otime'          => TimeToDate::class,
        'edit_time'      => TimeToDate::class,
        'ip'             => Ip::class,
        'name'           => SpecialChars::class,
        'edit_who'       => SpecialChars::class,
        'browser'        => SpecialChars::class,
        'attached_files' => 'array',
    ];

    protected $fillable = [
        'adm',
        'time',
        'user_id',
        'name',
        'text',
        'ip',
        'browser',
        'admin',
        'otvet',
        'otime',
        'edit_who',
        'edit_time',
        'edit_count',
        'attached_files',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function getIsOnlineAttribute(): bool
    {
        if ($this->user_id && $this->user !== null) {
            return $this->user->is_online;
        }
        return false;
    }
}
