<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryCategory extends Model
{
    protected $table = 'library_cats';

    public $timestamps = false;
}
