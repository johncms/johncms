<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Builder
 *
 * @property int $id
 * @property int $sub_id
 * @property int $time
 * @property int $user_id
 * @property string $text
 * @property string $reply
 * @property string $attributes
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AlbumComment extends Model
{
    public $timestamps = false;

    protected $table = 'cms_album_comments';

    protected $fillable = [
        'sub_id',
        'time',
        'user_id',
        'text',
        'reply',
        'attributes',
    ];

    protected $casts = [
        'sub_id'  => 'integer',
        'time'    => 'integer',
        'user_id' => 'integer',
    ];
}
