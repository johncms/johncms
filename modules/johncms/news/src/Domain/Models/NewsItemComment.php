<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $item_id
 * @property int $user_id
 * @property string $text
 * @property array<string, mixed> $user_data
 * @property list<int|string> $attached_files
 */
final class NewsItemComment extends Model
{
    protected $table = 'news_comments';
    protected $guarded = [];
    public $timestamps = false;
    protected $casts = ['item_id' => 'integer', 'user_id' => 'integer', 'user_data' => 'array', 'attached_files' => 'array'];
}
