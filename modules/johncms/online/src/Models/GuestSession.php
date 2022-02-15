<?php

declare(strict_types=1);

namespace Johncms\Online\Models;

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
}
