<?php

declare(strict_types=1);

namespace Johncms\Online\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GuestSession extends Model
{
    protected $table = 'guest_sessions';

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'id',
        'ip',
        'ip_via_proxy',
        'user_agent',
        'route',
        'route_params',
        'movements',
    ];

    protected $casts = [
        'route_params' => 'array',
    ];

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('updated_at', '>=', Carbon::now()->subMinutes(5));
    }
}
