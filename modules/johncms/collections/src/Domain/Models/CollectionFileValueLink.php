<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Models;

use Illuminate\Database\Eloquent\Model;

final class CollectionFileValueLink extends Model
{
    protected $table = 'collection_file_value_links';
    protected $guarded = [];
    public $timestamps = false;
    protected $casts = ['id' => 'integer', 'item_id' => 'integer', 'field_id' => 'integer', 'file_id' => 'integer'];
}
