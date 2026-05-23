<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Models;

use Illuminate\Database\Eloquent\Model;

final class DownloadCategory extends Model
{
    protected $table = 'download__category';
    public $timestamps = false;
    protected $fillable = ['refid', 'dir', 'sort', 'name', 'slug', 'desc', 'field', 'text', 'rus_name', 'total'];
}
