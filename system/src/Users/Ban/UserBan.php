<?php

declare(strict_types=1);

namespace Johncms\Users\Ban;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Builder
 * @property int $id
 * @property Carbon $active_from
 * @property Carbon $active_to
 * @property int $user_id
 * @property string $type
 * @property int $banned_by_id
 * @property string $reason
 * @property string $additional_fields
 */
class UserBan extends Model
{
    protected $table = 'user_bans';

    protected $fillable = [
        'active_from',
        'active_to',
        'user_id',
        'type',
        'banned_by_id',
        'reason',
        'additional_fields',
    ];

    protected $dates = [
        'active_from',
        'active_to',
    ];

    /**
     * Only active bans
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active_from', '<=', Carbon::now())
            ->where('active_to', '>=', Carbon::now());
    }
}
