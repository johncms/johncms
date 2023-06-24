<?php

declare(strict_types=1);

namespace Johncms\Content\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Builder
 * @property int $id
 * @property string $name
 * @property string $code
 */
class ContentType extends Model
{
    protected $table = 'content_types';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'code',
    ];
}
