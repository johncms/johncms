<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Бан по IP-адресу/диапазону (таблица cms_ban_ip).
 *
 * @mixin Builder
 * @property int $id
 * @property int $ip1
 * @property int $ip2
 * @property int $ban_type
 * @property string $link
 * @property string $who
 * @property string $reason
 * @property int $date
 */
final class BanIp extends Model
{
    protected $table = 'cms_ban_ip';

    public $timestamps = false;

    protected $fillable = [
        'ip1',
        'ip2',
        'ban_type',
        'link',
        'who',
        'reason',
        'date',
    ];

    protected $casts = [
        'ip1'      => 'integer',
        'ip2'      => 'integer',
        'ban_type' => 'integer',
        'date'     => 'integer',
    ];
}
