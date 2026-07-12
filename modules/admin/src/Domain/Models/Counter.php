<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Внешний счётчик/информер (таблица cms_counters).
 *
 * @mixin Builder
 * @property int $id
 * @property string $name
 * @property int $sort
 * @property string $link1
 * @property string $link2
 * @property int $mode
 * @property int $switch
 * @property int $require_cookie_consent
 */
final class Counter extends Model
{
    protected $table = 'cms_counters';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'sort',
        'link1',
        'link2',
        'mode',
        'switch',
        'require_cookie_consent',
    ];

    protected $casts = [
        'sort'                   => 'integer',
        'mode'                   => 'integer',
        'switch'                 => 'integer',
        'require_cookie_consent' => 'integer',
    ];
}
