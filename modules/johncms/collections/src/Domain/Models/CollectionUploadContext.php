<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Models;

use Illuminate\Database\Eloquent\Model;

final class CollectionUploadContext extends Model
{
    protected $table = 'collection_upload_contexts';
    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'token';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $casts = ['collection_id' => 'integer', 'user_id' => 'integer', 'item_id' => 'integer'];
}
