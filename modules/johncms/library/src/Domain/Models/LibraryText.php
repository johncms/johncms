<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Johncms\Modules\Library\Application\Services\LibraryArticlePathService;

class LibraryText extends Model
{
    protected $table = 'library_texts';

    public $timestamps = false;

    protected $fillable = [
        'cat_id',
        'name',
        'slug',
        'announce',
        'text',
        'uploader',
        'uploader_id',
        'premod',
        'comments',
        'time',
    ];

    public function getUrlAttribute(): string
    {
        return di(LibraryArticlePathService::class)->getArticleUrl($this);
    }
}
