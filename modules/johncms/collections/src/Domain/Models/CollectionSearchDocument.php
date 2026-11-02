<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Models;

use Illuminate\Database\Eloquent\Model;

final class CollectionSearchDocument extends Model
{
    protected $table = 'collection_search_documents';
    protected $guarded = [];
    public $timestamps = false;
}
