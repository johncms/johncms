<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Models;

use Illuminate\Database\Eloquent\Model;

final class DownloadBookmark extends Model
{
    protected $table = 'download__bookmark';
    public $timestamps = false;
    protected $fillable = ['file_id', 'user_id'];
}
