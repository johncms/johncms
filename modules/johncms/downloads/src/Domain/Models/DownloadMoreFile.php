<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Models;

use Illuminate\Database\Eloquent\Model;

final class DownloadMoreFile extends Model
{
    protected $table = 'download__more';
    public $timestamps = false;

    protected $fillable = ['refid', 'time', 'name', 'rus_name', 'size'];
}
