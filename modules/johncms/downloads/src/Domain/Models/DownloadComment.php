<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Models;

use Illuminate\Database\Eloquent\Model;

final class DownloadComment extends Model
{
    protected $table = 'download__comments';
    public $timestamps = false;
}
