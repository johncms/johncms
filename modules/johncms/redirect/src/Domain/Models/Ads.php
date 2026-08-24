<?php

declare(strict_types=1);

namespace Johncms\Modules\Redirect\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class Ads extends Model
{
    protected $table = 'cms_ads';

    public $timestamps = false;

    protected $fillable = [
        'link',
        'count',
    ];
}
