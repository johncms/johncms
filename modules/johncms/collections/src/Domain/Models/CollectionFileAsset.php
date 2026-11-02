<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Models;

use Illuminate\Database\Eloquent\Model;

final class CollectionFileAsset extends Model
{
    protected $table = 'collection_file_assets';
    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'file_id';
    public $incrementing = false;
    protected $casts = ['file_id' => 'integer', 'managed' => 'boolean'];
}
