<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryText extends Model
{
    protected $table = 'library_texts';

    public $timestamps = false;

    protected $fillable = [
        'cat_id',
        'name',
        'announce',
        'text',
        'uploader',
        'uploader_id',
        'premod',
        'comments',
        'time',
    ];
}
