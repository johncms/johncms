<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Models;

use Illuminate\Database\Eloquent\Model;

final class NewsItemVote extends Model
{
    protected $table = 'news_votes';
    protected $guarded = [];
    public $timestamps = false;
    protected $casts = ['item_id' => 'integer', 'user_id' => 'integer', 'vote' => 'integer'];
}
