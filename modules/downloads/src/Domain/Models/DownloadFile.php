<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Models;

use Illuminate\Database\Eloquent\Model;

final class DownloadFile extends Model
{
    protected $table = 'download__files';
    public $timestamps = false;
}
