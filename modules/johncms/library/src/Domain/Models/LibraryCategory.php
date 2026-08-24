<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;

class LibraryCategory extends Model
{
    protected $table = 'library_cats';

    public $timestamps = false;

    protected $fillable = [
        'parent',
        'name',
        'slug',
        'description',
        'dir',
        'pos',
        'user_add',
    ];

    public function getUrlAttribute(): string
    {
        return di(LibraryCategoryPathService::class)->getCategoryUrl($this);
    }
}
