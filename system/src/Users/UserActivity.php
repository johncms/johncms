<?php

declare(strict_types=1);

namespace Johncms\Users;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Builder
 * @property int $user_id
 * @property Carbon|null $last_visit
 * @property string|null $route
 * @property array|null $route_params
 * @property Carbon|null $last_post
 */
class UserActivity extends Model
{
    protected $table = 'user_activity';
    public $timestamps = false;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'user_id',
        'last_visit',
        'route',
        'route_params',
        'last_post',
    ];

    protected $dates = [
        'last_post',
        'last_visit',
    ];

    protected $casts = [
        'route' => 'array',
    ];
}
