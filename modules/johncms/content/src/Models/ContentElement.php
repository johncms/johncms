<?php

declare(strict_types=1);

namespace Johncms\Content\Models;

use Illuminate\Database\Eloquent\Model;

class ContentElement extends Model
{
    protected $table = 'content_elements';

    protected $fillable = [
        'content_type_id',
        'section_id',
        'name',
        'code',
        'detail_text',
    ];
}
