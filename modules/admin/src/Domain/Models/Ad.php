<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Рекламная ссылка/блок (таблица cms_ads).
 *
 * @mixin Builder
 * @property int $id
 * @property int $type
 * @property int $view
 * @property int $mesto
 * @property string $link
 * @property string $name
 * @property string $color
 * @property int $count_link
 * @property int $count
 * @property int $day
 * @property int $layout
 * @property int $show
 * @property int $time
 * @property int $to
 * @property int $bold
 * @property int $italic
 * @property int $underline
 */
final class Ad extends Model
{
    protected $table = 'cms_ads';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'view',
        'mesto',
        'link',
        'name',
        'color',
        'count_link',
        'count',
        'day',
        'layout',
        'show',
        'time',
        'to',
        'bold',
        'italic',
        'underline',
    ];

    protected $casts = [
        'type'       => 'integer',
        'view'       => 'integer',
        'mesto'      => 'integer',
        'count_link' => 'integer',
        'count'      => 'integer',
        'day'        => 'integer',
        'layout'     => 'integer',
        'show'       => 'integer',
        'time'       => 'integer',
        'to'         => 'integer',
        'bold'       => 'integer',
        'italic'     => 'integer',
        'underline'  => 'integer',
    ];
}
